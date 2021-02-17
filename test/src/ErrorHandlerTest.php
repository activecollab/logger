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

use ActiveCollab\Logger\ErrorHandler\ErrorHandler;
use ActiveCollab\Logger\ErrorHandler\ErrorHandlerInterface;
use ActiveCollab\Logger\Test\Base\TestCase;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use RuntimeException;
use Throwable;

/**
 * Test error handler.
 */
class ErrorHandlerTest extends TestCase
{
    /**
     * @var \Monolog\Formatter\LineFormatter
     */
    private $log_formatter;

    /**
     * @var \Monolog\Handler\TestHandler
     */
    private $log_handler;

    /**
     * @var \Monolog\Logger
     */
    private $log;

    /**
     * @var ErrorHandlerInterface
     */
    private $error_handler;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->log_formatter = new LineFormatter("%level_name%: %message% %context% %extra%\n");
        $this->log_handler = (new TestHandler())->setFormatter($this->log_formatter);
        $this->log = (new Logger('Active Collab Test'))->pushHandler($this->log_handler);

        $this->error_handler = new ErrorHandler($this->log);
        $this->error_handler->initialize();
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown(): void
    {
        $this->error_handler->restore();

        parent::tearDown();
    }

    /**
     * Test if user error logs an error.
     */
    public function testUserErrorLogsError()
    {
        $this->assertEquals(ErrorHandlerInterface::LOG_ERROR, $this->error_handler->getHowToHandleError(E_USER_ERROR));

        $this->assertCount(0, $this->log_handler->getRecords());
        trigger_error('This is an error', E_USER_ERROR);

        $this->assertCount(1, $this->log_handler->getRecords());
        $this->assertEquals('Error: {message}', $this->log_handler->getRecords()[0]['message']);
        $this->assertEquals(Logger::ERROR, $this->log_handler->getRecords()[0]['level']);
        $this->assertEquals('This is an error', $this->log_handler->getRecords()[0]['context']['message']);
        $this->assertEquals(E_USER_ERROR, $this->log_handler->getRecords()[0]['context']['code']);
        $this->assertEquals(__FILE__, $this->log_handler->getRecords()[0]['context']['file']);
        $this->assertNotEmpty($this->log_handler->getRecords()[0]['context']['line']);
        $this->assertNotEmpty($this->log_handler->getRecords()[0]['context']['trace']);
    }

    /**
     * Test if PHP warning logs an error.
     */
    public function testWarningLogsError()
    {
        $this->assertEquals(ErrorHandlerInterface::LOG_ERROR, $this->error_handler->getHowToHandleError(E_WARNING));

        $this->assertCount(0, $this->log_handler->getRecords());

        fopen('This file does not exist', 'r');

        $this->assertCount(1, $this->log_handler->getRecords());

        $this->assertCount(1, $this->log_handler->getRecords());
        $this->assertEquals('Error: {message}', $this->log_handler->getRecords()[0]['message']);
        $this->assertEquals(Logger::ERROR, $this->log_handler->getRecords()[0]['level']);
        $this->assertStringContainsString('failed to open stream', $this->log_handler->getRecords()[0]['context']['message']);
        $this->assertEquals(E_WARNING, $this->log_handler->getRecords()[0]['context']['code']);
        $this->assertEquals(__FILE__, $this->log_handler->getRecords()[0]['context']['file']);
        $this->assertNotEmpty($this->log_handler->getRecords()[0]['context']['line']);
    }

    /**
     * Test if we can silence errors, even critical ones, like parse errors.
     */
    public function testSilenceAnError()
    {
        $this->error_handler->setHowToHandleError(E_USER_ERROR, ErrorHandlerInterface::SILENCE);
        $this->assertEquals(ErrorHandlerInterface::SILENCE, $this->error_handler->getHowToHandleError(E_USER_ERROR));

        $this->assertCount(0, $this->log_handler->getRecords());
        trigger_error('This is an error', E_USER_ERROR);
        $this->assertCount(0, $this->log_handler->getRecords());
    }

    public function testSetHowToHandleErrorExceptionOnInvalidHandler()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->error_handler->setHowToHandleError(E_USER_ERROR, 'not valid');
    }

    public function testHandleExceptionReThrowsException()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Rethrow me");

        $this->assertTrue($this->error_handler->getReThrowException());
        $this->error_handler->handleException(new RuntimeException('Rethrow me'));
    }

    /**
     * Test how unhandled exception is handled (use parse error as an example).
     */
    public function testParseErrorLogsAnError()
    {
        $this->error_handler->setReThrowException(false);
        $this->assertFalse($this->error_handler->getReThrowException());

        $this->assertEquals(ErrorHandlerInterface::THROW_EXCEPTION, $this->error_handler->getHowToHandleError(E_PARSE));

        $this->assertCount(0, $this->log_handler->getRecords());

        try {
            eval('<?php not good');
            $this->fail('Code above should throw a parse error');
        } catch (\ParseError $e) {
            $this->error_handler->handleException($e);
        }

        $this->assertCount(1, $this->log_handler->getRecords());

        $this->assertEquals('Unhandled exception: {message}', $this->log_handler->getRecords()[0]['message']);
        $this->assertEquals(Logger::CRITICAL, $this->log_handler->getRecords()[0]['level']);
        $this->assertStringContainsString('syntax error', $this->log_handler->getRecords()[0]['context']['message']);
        $this->assertInstanceOf(Throwable::class, $this->log_handler->getRecords()[0]['context']['exception']);
    }

    /**
     * Test if exceptions are re-thrown by default.
     */
    public function testExceptionsAreReThrownByDefault()
    {
        $this->assertTrue($this->error_handler->getReThrowException());
    }
}
