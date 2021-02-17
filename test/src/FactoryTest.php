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
use InvalidArgumentException;
use RuntimeException;

/**
 * @package ActiveCollab\Logger\Test
 */
class FactoryTest extends TestCase
{
    /**
     * Test create creates a LoggerInterface instance.
     */
    public function testCreateCreatesLoggerInterfaceInstance()
    {
        $this->assertInstanceOf(LoggerInterface::class, (new Factory())->create('Active Collab', '1.0', 'test', LoggerInterface::LOG_FOR_PRODUCTION, LoggerInterface::FILE, $this->getTestLogsDir()));
    }

    public function testExceptionOnInvalidLoggerType()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Unknown logger type 'invalid logger'");

        (new Factory())->create('Active Collab', '1.0.0', 'development', LoggerInterface::LOG_FOR_DEBUG, 'invalid logger');
    }

    public function testExceptionOnEmptyLogDirPath()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Log directory argument is required");

        (new Factory())->create('Active Collab', '1.0.0', 'development', LoggerInterface::LOG_FOR_DEBUG, LoggerInterface::FILE, '');
    }

    public function testExceptionOnNonWritableLogDirPath()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("We can't write logs to 'unknown folder'");

        (new Factory())->create('Active Collab', '1.0.0', 'development', LoggerInterface::LOG_FOR_DEBUG, LoggerInterface::FILE, 'unknown folder');
    }

    /**
     * Test if additional env arguments propagate to logger.
     */
    public function testAppEnvArgumentsPropagateToLogger()
    {
        $factory = new Factory();
        $factory->setAdditionalEnvArguments(['additional_test_key' => '2']);

        $logger = $factory->create('Active Collab', '1.0.0', 'development', LoggerInterface::LOG_FOR_DEBUG, LoggerInterface::FILE, $this->getTestLogsDir());
        $this->assertInstanceOf(LoggerInterface::class, $logger);

        $this->assertArrayHasKey('additional_test_key', $logger->getAppEnv()->getArguments());
    }

    /**
     * Test if exception serializers propagate to logger level.
     */
    public function testExceptionSerializersPropagateToLogger()
    {
        $factory = new Factory();
        $this->assertCount(0, $factory->getExceptionSerializers());

        $logger = $factory->create('Active Collab', '1.0.0', 'development', LoggerInterface::LOG_FOR_DEBUG, LoggerInterface::FILE, $this->getTestLogsDir());
        $this->assertCount(0, $logger->getExceptionSerializers());

        $factory->addExceptionSerializer(function () {});
        $factory->addExceptionSerializer(function () {});

        $this->assertCount(2, $factory->getExceptionSerializers());

        $logger = $factory->create('Active Collab', '1.0.0', 'development', LoggerInterface::LOG_FOR_DEBUG, LoggerInterface::FILE, $this->getTestLogsDir());
        $this->assertCount(2, $logger->getExceptionSerializers());
    }
}
