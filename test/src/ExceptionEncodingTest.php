<?php

/*
 * This file is part of the Active Collab Logger.
 *
 * (c) A51 doo <info@activecollab.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ActiveCollab\Logger\Test;

use ActiveCollab\Logger\Factory\Factory;
use ActiveCollab\Logger\LoggerInterface;
use ActiveCollab\Logger\Test\Base\TestCase;
use ActiveCollab\Logger\Test\Fixtures\Error\Error;
use ActiveCollab\Logger\Test\Fixtures\Error\FileDnxError;
use LogicException;
use RuntimeException;

/**
 * @package ActiveCollab\Logger\Test
 */
class ExceptionEncodingTest extends TestCase
{
    /**
     * Test exception serialization.
     */
    public function testExceptionSerialization()
    {
        $logger = $this->getLogger();

        $first = new LogicException('This is a logic exception');
        $second = new RuntimeException('Something is not working correctly', 123, $first);

        $logger->error('Failed due to exception', [
            'first' => 'argument',
            'exception' => $second,
        ]);

        $this->assertCount(1, $logger->getBuffer());

        $log_entry = $logger->getBuffer()[0];

        $this->assertEquals('Something is not working correctly', $log_entry['context']['exception']);
        $this->assertEquals('RuntimeException', $log_entry['context']['exception_class']);

        $this->assertEquals(123, $log_entry['context']['exception_code']);
        $this->assertEquals(__FILE__, $log_entry['context']['exception_file']);
        $this->assertNotEmpty($log_entry['context']['exception_trace']);

        $this->assertEquals('This is a logic exception', $log_entry['context']['exception_previous']);
        $this->assertEquals('LogicException', $log_entry['context']['exception_previous_class']);
        $this->assertEquals(0, $log_entry['context']['exception_previous_code']);
        $this->assertEquals(__FILE__, $log_entry['context']['exception_previous_file']);
        $this->assertNotEmpty($log_entry['context']['exception_previous_line']);
        $this->assertNotEmpty($log_entry['context']['exception_previous_trace']);
        $this->assertArrayNotHasKey('exception_previous_previous', $log_entry['context']);
    }

    /**
     * Test if angie errors add extra data to the log.
     */
    public function testAngieErrorException()
    {
        $logger = $this->getLogger(null, function (LoggerInterface &$logger) {
            $logger->addExceptionSerializer(function ($argument_name, $exception, array &$context) {
                if ($exception instanceof Error) {
                    foreach ($exception->getParams() as $k => $v) {
                        $context["{$argument_name}_extra_param_{$k}"] = $v;
                    }
                }
            });
        });

        $validation_error = new FileDnxError(__FILE__);

        $logger->error('Client facing exception: ' . $validation_error->getMessage(), [
            'exception' => $validation_error,
        ]);

        $this->assertCount(1, $logger->getBuffer());

        $log_entry = $logger->getBuffer()[0];

        $this->assertEquals($validation_error->getMessage(), $log_entry['context']['exception']);
        $this->assertEquals(FileDnxError::class, $log_entry['context']['exception_class']);
        $this->assertArrayHasKey('exception_extra_param_path', $log_entry['context']);
    }

    /**
     * Create a logger instance and optionally modify factory or logger instances.
     *
     * @param  callable|null   $with_factory
     * @param  callable|null   $with_logger
     * @return LoggerInterface
     */
    private function getLogger(callable $with_factory = null, callable $with_logger = null)
    {
        $factory = new Factory();

        if ($with_factory) {
            $with_factory($factory);
        }

        $logger = $factory->create('Active Collab', '1.0.0', 'development', LoggerInterface::LOG_FOR_DEBUG, LoggerInterface::BLACKHOLE);
        $this->assertCount(0, $logger->getBuffer());

        if ($with_logger) {
            $with_logger($logger);
        }

        return $logger;
    }
}
