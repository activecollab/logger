<?php

/*
 * This file is part of the Active Collab Logger.
 *
 * (c) A51 doo <info@activecollab.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ActiveCollab\Logger;

use ActiveCollab\Logger\AppEnv\AppEnvInterface;
use ActiveCollab\Logger\AppRequest\AppRequestInterface;
use ActiveCollab\Logger\AppResponse\AppResponseInterface;
use ActiveCollab\Logger\ExceptionSerializers\ExceptionSerializersTrait;
use Exception;
use Monolog\Logger as MonologLogger;
use Throwable;

/**
 * @package ActiveCollab\Logger
 */
class Logger implements LoggerInterface
{
    use ExceptionSerializersTrait;

    /**
     * @var MonologLogger
     */
    private $logger;

    /**
     * @var int
     */
    private $split_strings_in_chunks = 0;

    /**
     * @var array
     */
    private $env_arguments = [];

    /**
     * @var AppEnvInterface
     */
    private $app_env;

    /**
     * @var AppRequestInterface
     */
    private $app_request;

    /**
     * @var AppResponseInterface
     */
    private $app_response;

    /**
     * @var array
     */
    private $request_signature = [];

    /**
     * @var array
     */
    private $request_summary_arguments = [];

    /**
     * @var array
     */
    private $request_arguments = [];

    /**
     * @var array
     */
    private $response_summary_arguments = [];

    /**
     * @var array
     */
    private $buffer = [];

    /**
     * @param MonologLogger   $logger
     * @param AppEnvInterface $app_env
     */
    public function __construct(MonologLogger $logger, AppEnvInterface $app_env)
    {
        $this->logger = $logger;
        $this->setAppEnv($app_env);
    }

    /**
     * {@inheritdoc}
     */
    public function getSplitStringsInChunks()
    {
        return $this->split_strings_in_chunks;
    }

    /**
     * {@inheritdoc}
     */
    public function &setSplitStringsInChunks($value)
    {
        $this->split_strings_in_chunks = (int) $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAppEnv()
    {
        return $this->app_env;
    }

    /**
     * {@inheritdoc}
     */
    public function &setAppEnv(AppEnvInterface $env)
    {
        $this->app_env = $env;
        $this->env_arguments = $env->getArguments();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAppRequest()
    {
        return $this->app_request;
    }

    /**
     * {@inheritdoc}
     */
    public function &setAppRequest(AppRequestInterface $request)
    {
        $this->app_request = $request;

        $this->request_signature = $request->getSignature();
        $this->request_summary_arguments = $request->getSummaryArguments();

        if (!empty($request->getSessionId()) && !empty($request->getRequestId())) {
            $this->request_arguments = [
                'session_id' => $request->getSessionId(),
                'request_id' => $request->getRequestId(),
            ];
        }

        $this->flushBuffer();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAppRequestArguments()
    {
        return $this->request_arguments;
    }

    /**
     * {@inheritdoc}
     */
    public function getAppResponse()
    {
        return $this->app_response;
    }

    /**
     * {@inheritdoc}
     */
    public function &setAppResponse(AppResponseInterface $response)
    {
        $this->app_response = $response;

        $this->response_summary_arguments = $response->getSummaryArguments();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getBuffer()
    {
        return $this->buffer;
    }

    /**
     * {@inheritdoc}
     */
    public function flushBuffer($force = false)
    {
        if (!empty($this->request_arguments) || $force) {
            foreach ($this->buffer as $log_entry) {
                $this->logger->log($log_entry['level'], $log_entry['message'], array_merge($this->env_arguments, $this->request_arguments, $log_entry['context']));
            }

            $this->buffer = [];
        }
    }

    /**
     * @var bool
     */
    private $shutdown_function_registered = false;

    /**
     * {@inheritdoc}
     */
    public function flushBufferOnShutdown()
    {
        if ($this->shutdown_function_registered) {
            throw new \LogicException('Buffer flush function should be registered only once');

        }
        register_shutdown_function([$this, 'flushBuffer'], true);
        $this->shutdown_function_registered = true;
    }

    /**
     * Add message to the buffer.
     *
     * @param  int    $level
     * @param  string $message
     * @param  array  $context
     * @param  bool   $flush_buffer
     * @return bool
     */
    private function buffer($level, $message, array $context, $flush_buffer = true)
    {
        $log_entry = ['level' => $level, 'message' => $message, 'context' => []];

        foreach ($context as $k => $v) {
            $log_entry['context'][$k] = $this->serializeContextArgument($k, $v, $log_entry['context']);
        }

        $this->buffer[] = $log_entry;

        if ($flush_buffer) {
            $this->flushBuffer();
        }

        return true;
    }

    /**
     * Return true if $value is an exception.
     *
     * @param  mixed $value
     * @return bool
     */
    private function isException($value)
    {
        return $value instanceof Exception || $value instanceof Throwable;
    }

    /**
     * @param  string $argument_name
     * @param  mixed  $value
     * @param  array  $context
     * @return string
     */
    private function serializeContextArgument($argument_name, $value, array &$context)
    {
        if ($this->isException($value)) {
            return $this->serializeException($argument_name, $value, $context);
        } elseif ($this->split_strings_in_chunks && is_string($value) && mb_strlen($value) > $this->split_strings_in_chunks) {
            do {
                $parts[] = mb_substr($value, 0, $this->split_strings_in_chunks);
                $value = mb_substr($value, $this->split_strings_in_chunks);
            } while (!empty($value));

            $i = 0;
            $first_value_chunk = '';

            foreach ($parts as $part) {
                if (empty($i)) {
                    $first_value_chunk = $part;
                } else {
                    $context["{$argument_name}_{$i}"] = $part;
                }

                ++$i;
            }

            return $first_value_chunk;
        } else {
            return $value;
        }
    }

    /**
     * @var int
     */
    private $exception_encoding_level = 1;

    /**
     * @param                      $argument_name
     * @param  Exception|Throwable $exception
     * @param  array               $context
     * @return string
     */
    private function serializeException($argument_name, $exception, array &$context)
    {
        $context[$argument_name] = $exception->getMessage(); // Reserve it as first key

        $context["{$argument_name}_class"] = get_class($exception);
        $context["{$argument_name}_code"] = $exception->getCode();
        $context["{$argument_name}_file"] = $exception->getFile();
        $context["{$argument_name}_line"] = $exception->getLine();
        $context["{$argument_name}_trace"] = $exception->getTraceAsString();
        $context["{$argument_name}_class"] = get_class($exception);

        foreach ($this->getExceptionSerializers() as $exception_serializer) {
            call_user_func_array($exception_serializer, [$argument_name, $exception, &$context]);
        }

        if ($exception->getPrevious() && $this->exception_encoding_level <= 3) {
            ++$this->exception_encoding_level;

            $this->serializeException("{$argument_name}_previous", $exception->getPrevious(), $context);
        }

        return $exception->getMessage();
    }

    // ---------------------------------------------------
    //  Special loggers
    // ---------------------------------------------------

    /**
     * {@inheritdoc}
     */
    public function requestSummary($exec_time_in_s, $memory_usage, $query_count, $query_exec_time)
    {
        $event_name = $this->env_arguments['sapi'] == 'cli' ? 'cli_request' : 'http_request';

        return $this->event($event_name, 'Request {signature} done in {exec_time} miliseconds', array_merge($this->request_summary_arguments, $this->response_summary_arguments, [
            'signature' => $this->request_signature,
            'exec_time' => $exec_time_in_s > 0 ? ceil($exec_time_in_s * 1000) : 0, // Log execution time in ms
            'memory_usage' => $memory_usage,
            'query_count' => $query_count,
            'query_time' => $query_exec_time > 0 ? ceil($query_exec_time * 1000) : 0, // Log execution time in ms
        ]));
    }

    /**
     * {@inheritdoc}
     */
    public function event($event_name, $message, array $context = [])
    {
        return $this->info($message, array_merge(['event' => $event_name], $context));
    }

    // ---------------------------------------------------
    //  Logger interface implementation
    // ---------------------------------------------------

    /**
     * {@inheritdoc}
     */
    public function emergency($message, array $context = [])
    {
        return $this->log(MonologLogger::EMERGENCY, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function alert($message, array $context = [])
    {
        return $this->log(MonologLogger::ALERT, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function critical($message, array $context = [])
    {
        return $this->log(MonologLogger::CRITICAL, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function error($message, array $context = [])
    {
        return $this->log(MonologLogger::ERROR, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function warning($message, array $context = [])
    {
        return $this->log(MonologLogger::WARNING, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function notice($message, array $context = [])
    {
        return $this->log(MonologLogger::NOTICE, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function info($message, array $context = [])
    {
        return $this->log(MonologLogger::INFO, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function debug($message, array $context = [])
    {
        return $this->log(MonologLogger::DEBUG, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function log($level, $message, array $context = [])
    {
        return $this->buffer($level, $message, $context);
    }
}
