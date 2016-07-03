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

use ActiveCollab\Logger\AppRequest\CliRequest;
use ActiveCollab\Logger\AppRequest\HttpRequest;
use ActiveCollab\Logger\AppResponse\HttpResponse;
use ActiveCollab\Logger\Factory\Factory;
use ActiveCollab\Logger\Factory\FactoryInterface;
use ActiveCollab\Logger\LoggerInterface;
use ActiveCollab\Logger\Test\Base\TestCase;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;

/**
 * @package angie.tests
 */
class LoggerShutdownTest extends TestCase
{
    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Buffer flush function should be registered only once
     */
    public function testLogicExceptionOnMultiShutdownCalls()
    {
        $logger = (new Factory())->create('Active Collab', '1.0.0', 'development', LoggerInterface::LOG_FOR_DEBUG, LoggerInterface::BLACKHOLE);

        $logger->flushBufferOnShutdown();
        $logger->flushBufferOnShutdown();
    }
}
