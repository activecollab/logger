<?php

/*
 * This file is part of the Active Collab Logger.
 *
 * (c) A51 doo <info@activecollab.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ActiveCollab\Logger\Factory;

use ActiveCollab\Logger\AppEnv\AppEnv;
use ActiveCollab\Logger\ExceptionSerializers\ExceptionSerializersTrait;
use ActiveCollab\Logger\Logger;
use ActiveCollab\Logger\LoggerInterface;
use Gelf\Publisher;
use Gelf\Transport\UdpTransport;
use InvalidArgumentException;
use Monolog\Formatter\GelfMessageFormatter;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\GelfHandler;
use Monolog\Handler\NullHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\TestHandler;
use Monolog\Logger as MonologLogger;
use Monolog\Processor\PsrLogMessageProcessor;
use RuntimeException;

/**
 * @package ActiveCollab\Logger
 */
class Factory implements FactoryInterface
{
    use ExceptionSerializersTrait;

    /**
     * {@inheritdoc}
     */
    public function create($app_name, $app_version, $app_env, $log_level, $logger_type, ...$logger_arguments)
    {
        $monolog_logger = new MonologLogger($app_name);

        $formatter = new LineFormatter("[%datetime%] %level_name%: %message% %context% %extra%\n", 'Y-m-d H:i:s');

        $split_strings_in_chunks = 0;

        switch ($logger_type) {
            case LoggerInterface::FILE:
                $log_dir = isset($logger_arguments[0]) && $logger_arguments[0] ? $logger_arguments[0] : '';
                $log_file = empty($logger_arguments[1]) ? 'log.txt' : $logger_arguments[1];
                $log_file_permissions = empty($logger_arguments[2]) ? 0644 : $logger_arguments[2];

                if (empty($log_dir)) {
                    throw new InvalidArgumentException('Log directory argument is required');
                }

                if (!is_writable($log_dir)) {
                    throw new RuntimeException("We can't write logs to '$log_dir'");
                }

                $handler = new RotatingFileHandler("$log_dir/$log_file", 7, $log_level, true, $log_file_permissions);

                break;
            case LoggerInterface::GRAYLOG:
                $graylog_host = empty($logger_arguments[0]) ? UdpTransport::DEFAULT_HOST : $logger_arguments[0];
                $graylog_port = empty($logger_arguments[1]) ? UdpTransport::DEFAULT_PORT : $logger_arguments[1];

                $publisher = new Publisher(new UdpTransport($graylog_host, $graylog_port));
                $handler = new GelfHandler($publisher, $log_level);
                $formatter = new GelfMessageFormatter(null, null, '');

                $split_strings_in_chunks = 32766;

                break;
            case LoggerInterface::BLACKHOLE:
                $handler = new NullHandler($log_level);
                break;
            case LoggerInterface::TEST:
                $handler = new TestHandler();
                break;
            default:
                throw new InvalidArgumentException("Unknown logger type '$logger_type'");
        }

        $handler->setFormatter($formatter);
        $handler->pushProcessor(new PsrLogMessageProcessor());

        $monolog_logger->pushHandler($handler);

        $logger = new Logger($monolog_logger, new AppEnv($app_name, $app_version, $app_env, $this->getAdditionalEvnArguments()));
        foreach ($this->getExceptionSerializers() as $exception_serializer) {
            $logger->addExceptionSerializer($exception_serializer);
        }

        if ($split_strings_in_chunks) {
            $logger->setSplitStringsInChunks($split_strings_in_chunks);
        }

        return $logger;
    }

    /**
     * @var array
     */
    private $additional_evn_arguments = [];

    /**
     * {@inheritdoc}
     */
    public function getAdditionalEvnArguments()
    {
        return $this->additional_evn_arguments;
    }

    /**
     * {@inheritdoc}
     */
    public function &setAdditionalEnvArguments(array $args)
    {
        $this->additional_evn_arguments = $args;

        return $this;
    }
}
