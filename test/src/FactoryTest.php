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
        $this->assertInstanceOf(LoggerInterface::class, (new Factory())->create('Active Collab', '1.0', 'test', LoggerInterface::LOG_FOR_PRODUCTION, LoggerInterface::FILE, $this->getTestDir() . '/logs'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Unknown logger type 'invalid logger'
     */
    public function testExceptionOnInvalidLoggerType()
    {
        (new Factory())->create('Active Collab', '1.0.0', 'development', LoggerInterface::LOG_FOR_DEBUG, 'invalid logger');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Log directory argument is required
     */
    public function testExceptionOnEmptyLogDirPath()
    {
        (new Factory())->create('Active Collab', '1.0.0', 'development', LoggerInterface::LOG_FOR_DEBUG, LoggerInterface::FILE, '');
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage We can't write logs to 'unknown folder'
     */
    public function testExceptionOnNonWritableLogDirPath()
    {
        (new Factory())->create('Active Collab', '1.0.0', 'development', LoggerInterface::LOG_FOR_DEBUG, LoggerInterface::FILE, 'unknown folder');
    }
}
