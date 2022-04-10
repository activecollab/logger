<?php

/*
 * This file is part of the Active Collab Logger.
 *
 * (c) A51 doo <info@activecollab.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

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
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\NullHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\TestHandler;
use Monolog\Logger as MonologLogger;
use Monolog\Processor\PsrLogMessageProcessor;
use RuntimeException;

class Factory implements FactoryInterface
{
    use ExceptionSerializersTrait;

    public function createWithHandlers(
        string $app_name,
        string $app_version,
        string $app_env,
        HandlerInterface ...$handlers
    ): LoggerInterface
    {
        $monolog_logger = new MonologLogger($app_name);

        $split_strings_in_chunks = 0;

        foreach ($handlers as $handler) {
            $monolog_logger->pushHandler($handler);

            if ($handler instanceof GelfHandler) {
                $split_strings_in_chunks = 32766;
            }
        }

        $logger = new Logger(
            $monolog_logger,
            new AppEnv(
                $app_name,
                $app_version,
                $app_env,
                $this->getAdditionalEnvArguments()
            )
        );

        foreach ($this->getExceptionSerializers() as $exception_serializer) {
            $logger->addExceptionSerializer($exception_serializer);
        }

        if ($split_strings_in_chunks) {
            $logger->setSplitStringsInChunks($split_strings_in_chunks);
        }

        return $logger;
    }

    public function create(
        string $app_name,
        string $app_version,
        string $app_env,
        int $log_level,
        string $logger_type,
        ...$logger_arguments
    ): LoggerInterface
    {
        $formatter = new LineFormatter(
            "[%datetime%] %level_name%: %message% %context% %extra%\n",
            'Y-m-d H:i:s'
        );

        switch ($logger_type) {
            case LoggerInterface::FILE:
                $log_dir = isset($logger_arguments[0]) && $logger_arguments[0] ? $logger_arguments[0] : '';
                $log_file = empty($logger_arguments[1]) ? 'log.txt' : $logger_arguments[1];
                $log_file_permissions = empty($logger_arguments[2]) ? 0644 : $logger_arguments[2];

                if (empty($log_dir)) {
                    throw new InvalidArgumentException('Log directory argument is required.');
                }

                if (!is_writable($log_dir)) {
                    throw new RuntimeException(sprintf("We can't write logs to '%s'.", $log_dir));
                }

                $handler = new RotatingFileHandler(
                    "$log_dir/$log_file",
                    7,
                    $log_level,
                    true,
                    $log_file_permissions
                );

                break;
            case LoggerInterface::GRAYLOG:
                $graylog_host = empty($logger_arguments[0]) ? UdpTransport::DEFAULT_HOST : $logger_arguments[0];
                $graylog_port = empty($logger_arguments[1]) ? UdpTransport::DEFAULT_PORT : $logger_arguments[1];

                $publisher = new Publisher(new UdpTransport($graylog_host, $graylog_port));
                $handler = new GelfHandler($publisher, $log_level);
                $formatter = new GelfMessageFormatter(null, null, '');

                break;
            case LoggerInterface::BLACKHOLE:
                $handler = new NullHandler($log_level);
                break;
            case LoggerInterface::TEST:
                $handler = new TestHandler();
                break;
            default:
                throw new InvalidArgumentException(
                    sprintf("Unknown logger type '%s'.", $logger_type)
                );
        }

        $handler->setFormatter($formatter);
        $handler->pushProcessor(new PsrLogMessageProcessor());

        return $this->createWithHandlers(
            $app_name,
            $app_version,
            $app_env,
            $handler
        );
    }

    private array $additional_evn_arguments = [];

    public function getAdditionalEnvArguments(): array
    {
        return $this->additional_evn_arguments;
    }

    public function setAdditionalEnvArguments(array $args): FactoryInterface
    {
        $this->additional_evn_arguments = $args;

        return $this;
    }
}
