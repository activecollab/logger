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
use ActiveCollab\Logger\LoggerInterface;
use ActiveCollab\Logger\Test\Base\TestCase;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;

/**
 * @package angie.tests
 */
class LoggerTest extends TestCase
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $factory = new Factory();

        $this->logger = $factory->create(
            'Active Collab',
            '1.0.0',
            'development',
            LoggerInterface::LOG_FOR_DEBUG,
            LoggerInterface::BLACKHOLE
        );
        $this->assertCount(0, $this->logger->getBuffer());
    }

    /**
     * Test HTTP request signature.
     */
    public function testHttpRequestSignature()
    {
        $request = new HttpRequest((new ServerRequest([], [], '/projects', 'GET')));
        $this->assertEquals('GET /projects', $request->getSignature());
    }

    /**
     * Test HTTP request signature with query string.
     */
    public function testHttpRequestSignatureWithQueryString()
    {
        $request = new HttpRequest(
            (new ServerRequest(
                [],
                [],
                '/projects',
                'GET',
                'php://input',
                [],
                [],
                [
                    'one' => 'two',
                    'three' => 'four',
                ]
            ))
        );
        $this->assertEquals('GET /projects?one=two&three=four', $request->getSignature());
    }

    /**
     * Test HTTP request signature with long query string.
     */
    public function testHttpRequestSignatureWithLongQueryString()
    {
        $request = new HttpRequest(
            (new ServerRequest(
                [],
                [],
                '/projects/names',
                'POST',
                'php://input',
                [],
                [],
                [
                    'long_arg' => '2q04C111Zzuk8g6hi12a9w5A4l355TVp2q04C111Zzuk8g6hi12a9w5A4l355TVp',
                ]
            ))
        );
        $this->assertEquals('POST /projects/names?long_arg=2q04C111Zzuk8g6hi12a9w5A4l355TVp2q04...', $request->getSignature());
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Command 'php -S 0.0.0.0:8888 -t public public/.router-php-server' is not an Active Collab CLI command
     */
    public function testExceptionOnInvalidPhpScriptCommand()
    {
        new CliRequest(123, ['php', '-S', '0.0.0.0:8888', '-t', 'public', 'public/.router-php-server']);
    }

    /**
     * Test regular CLI request signature.
     */
    public function testCliRequestSignature()
    {
        $request = new CliRequest(123, ['php', 'tasks/import_email.php']);
        $this->assertEquals('~tasks/import_email.php', $request->getSignature());
    }

    /**
     * Test regular CLI request signature with arguments.
     */
    public function testCliRequestSignatureWithArguments()
    {
        $request = new CliRequest(123, ['php', 'tasks/import_email.php', '/path/to/email.eml']);
        $this->assertEquals('~tasks/import_email.php /path/to/email.eml', $request->getSignature());
    }

    /**
     * Test activecollab-cli.php CLI request signature when CLI command has no extra arguments.
     */
    public function testActiveCollabCliRequestSignature()
    {
        $request = new CliRequest(123, ['php', 'tasks/activecollab-cli.php', 'dev:new_migration']);
        $this->assertEquals('~dev:new_migration', $request->getSignature());
    }

    /**
     * Test activecollab-cli.php CLI request with command arguments.
     */
    public function testActiveCollabCliRequestSignatureWithArguments()
    {
        $request = new CliRequest(
            123,
            [
                'php',
                'tasks/activecollab-cli.php',
                'dev:new_migration',
                'new feature',
            ]
        );
        $this->assertEquals("~dev:new_migration 'new feature'", $request->getSignature());
    }

    /**
     * Test activecollab-cli.php CLI request with long list of command arguments.
     */
    public function testActiveCollabCliRequestSignatureWithLongArguments()
    {
        $request = new CliRequest(
            123,
            [
                'php',
                'tasks/activecollab-cli.php',
                'dev:new_migration',
                'new feature',
                '-c',
                '2q04C111Zzuk8g6hi12a9w5A4l355TVp2q04C111Zzuk8g6hi12a9w5A4l355TVp',
            ]
        );
        $this->assertEquals(
            "~dev:new_migration 'new feature' -c 2q04C111Zzuk8g6hi12a9w5A4l35...",
            $request->getSignature()
        );
    }

    public function testCliRequestWithCustomScriptName()
    {
        $request = new CliRequest(
            123,
            [
                'php',
                'app/bin/shepherd.php',
                'dev:new_migration',
                'new feature'
            ],
            'shepherd.php'
        );
        $this->assertEquals("~dev:new_migration 'new feature'", $request->getSignature());
    }

    /**
     * Test environment arguments.
     */
    public function testEnvArguments()
    {
        $this->assertCount(4, $this->logger->getAppEnv()->getArguments());

        $this->assertEquals('Active Collab', $this->logger->getAppEnv()->getArguments()['app']);
        $this->assertEquals('1.0.0', $this->logger->getAppEnv()->getArguments()['ver']);
        $this->assertEquals('development', $this->logger->getAppEnv()->getArguments()['env']);
        $this->assertEquals(php_sapi_name(), $this->logger->getAppEnv()->getArguments()['sapi']);
    }

    /**
     * Test environment arguments, with additional arguments forwarded via logger factory.
     */
    public function testEnvWithAdditionalArguments()
    {
        $factory = new Factory();
        $factory->setAdditionalEnvArguments([
            'account_id' => 123,
            'owner_email' => 'john.doe@example.com',
        ]);

        $logger = $factory->create(
            'Active Collab',
            '1.0.0',
            'development',
            LoggerInterface::LOG_FOR_DEBUG,
            LoggerInterface::BLACKHOLE
        );

        $this->assertArrayHasKey('account_id', $logger->getAppEnv()->getArguments());
        $this->assertArrayHasKey('owner_email', $logger->getAppEnv()->getArguments());
    }

    /**
     * Test logs are kept until request is set.
     */
    public function testLogsAreKeptUntilRequestIsSet()
    {
        $this->logger->info('One');
        $this->logger->debug('Two');
        $this->logger->error('Three');

        $this->assertCount(3, $this->logger->getBuffer());
    }

    /**
     * Test flush keeps messages if request arguments are not yet set.
     */
    public function testFlushKeepsMessagesIfRequestIsNotSetByDefault()
    {
        $this->logger->info('One');
        $this->logger->debug('Two');
        $this->logger->error('Three');

        $this->assertCount(3, $this->logger->getBuffer());

        $this->logger->flushBuffer();

        $this->assertCount(3, $this->logger->getBuffer());
    }

    /**
     * Test flush can be forced.
     */
    public function testFlushCanBeForced()
    {
        $this->logger->info('One');
        $this->logger->debug('Two');
        $this->logger->error('Three');

        $this->assertCount(3, $this->logger->getBuffer());

        $this->logger->flushBuffer(true);

        $this->assertCount(0, $this->logger->getBuffer());
    }

    /**
     * Test if setting an empty request (no session ID and request ID) does not flush a buffer.
     */
    public function testSettingEmptyRequestDoesNotFlushBuffer()
    {
        $this->logger->info('One');
        $this->logger->debug('Two');
        $this->logger->error('Three');

        $this->assertCount(3, $this->logger->getBuffer());

        $this->logger->setAppRequest(
            new HttpRequest(
                new ServerRequest([], [], '/projects', 'GET')
            )
        );
        $this->assertCount(3, $this->logger->getBuffer());

        $this->logger->setAppRequest(
            new HttpRequest(
                (new ServerRequest([], [], '/projects', 'GET'))
                    ->withAttribute('session_id', '')
                    ->withAttribute('request_id', '')
            )
        );
        $this->assertCount(3, $this->logger->getBuffer());
    }

    /**
     * Test setting a request instance also flushes the data.
     */
    public function testSettingRequestWithSessionAndRequestIdFlushesBuffer()
    {
        $this->logger->info('One');
        $this->logger->debug('Two');
        $this->logger->error('Three');

        $this->assertCount(3, $this->logger->getBuffer());

        $this->logger->setAppRequest(
            new HttpRequest(
                (new ServerRequest([], [], '/projects', 'GET'))
                    ->withAttribute('session_id', 123)
                    ->withAttribute('request_id', 321)
            )
        );

        $this->assertCount(0, $this->logger->getBuffer());
    }

    /**
     * Test if setting updated request that has request ID and session ID flushes buffer.
     */
    public function testSettingUpdatedRequestFlushesBuffer()
    {
        $this->logger->info('One');
        $this->logger->debug('Two');
        $this->logger->error('Three');

        $this->assertCount(3, $this->logger->getBuffer());

        $request = new ServerRequest([], [], '/projects', 'GET');

        $this->logger->setAppRequest(new HttpRequest($request));
        $this->assertCount(3, $this->logger->getBuffer());

        $request = $request->withAttribute('session_id', 123)->withAttribute('request_id', 321);

        $this->logger->setAppRequest(new HttpRequest($request));
        $this->assertCount(0, $this->logger->getBuffer());
    }

    /**
     * Test request arguments.
     */
    public function testRequestArguments()
    {
        $this->logger->setAppRequest(
            new HttpRequest(
                (new ServerRequest([], [], '/projects', 'GET'))
                    ->withAttribute('session_id', 'xyz')
                    ->withAttribute('request_id', '123'))

        );

        $this->assertCount(2, $this->logger->getAppRequestArguments());
        $this->assertEquals('xyz', $this->logger->getAppRequestArguments()['session_id']);
        $this->assertEquals('123', $this->logger->getAppRequestArguments()['request_id']);
    }

    /**
     * Test app response.
     */
    public function testAppResponse()
    {
        $response = (new Response())->withStatus(404, 'Not found');
        $app_response = new HttpResponse($response);

        $this->assertCount(2, $app_response->getSummaryArguments());
        $this->assertEquals(404, $app_response->getSummaryArguments()['status_code']);
        $this->assertEquals('Not found', $app_response->getSummaryArguments()['reason_phrase']);
    }

    /**
     * Test if string splitting is disabled by default.
     */
    public function testSplitLongStringsIsOffByDefault()
    {
        $this->assertSame(0, $this->logger->getSplitStringsInChunks());
    }

    /**
     * Test if long strings are properly split into chunks.
     */
    public function testSplitLongStrings()
    {
        $this->assertSame(10, $this->logger->setSplitStringsInChunks(10)->getSplitStringsInChunks());

        $this->logger->info('This is a short message', [
            'short_arg' => 'Short',
            'long_arg' => 'This is a bit longer argument, so we can test it',
        ]);

        $this->assertCount(1, $this->logger->getBuffer());

        $this->assertEquals('Short', $this->logger->getBuffer()[0]['context']['short_arg']);
        $this->assertEquals('This is a ', $this->logger->getBuffer()[0]['context']['long_arg']);
        $this->assertEquals('bit longer', $this->logger->getBuffer()[0]['context']['long_arg_1']);
        $this->assertEquals(' argument,', $this->logger->getBuffer()[0]['context']['long_arg_2']);
        $this->assertEquals(' so we can', $this->logger->getBuffer()[0]['context']['long_arg_3']);
        $this->assertEquals(' test it', $this->logger->getBuffer()[0]['context']['long_arg_4']);

        $this->assertArrayNotHasKey('long_arg_0', $this->logger->getBuffer()[0]['context']);
        $this->assertArrayNotHasKey('long_arg_5', $this->logger->getBuffer()[0]['context']);
    }

    /**
     * Test event logging.
     */
    public function testEventLogging()
    {
        $this->logger->event('task_created', 'This is a short message', [
            'arg1' => 'Arg1',
        ]);

        $this->assertCount(1, $this->logger->getBuffer());
        $this->assertEquals('task_created', $this->logger->getBuffer()[0]['context']['event']);
    }

    /**
     * Test if request summaries are logged properly.
     */
    public function testRequestSummary()
    {
        $this->logger->requestSummary(
            0.356,
            1024 * 1024,
            15,
            0.235
        );

        $this->assertCount(1, $this->logger->getBuffer());

        $this->assertEquals(
            'Request {signature} done in {exec_time} miliseconds',
            $this->logger->getBuffer()[0]['message']
        );
        $this->assertArrayHasKey('event', $this->logger->getBuffer()[0]['context']);
        $this->assertEquals(356, $this->logger->getBuffer()[0]['context']['exec_time']);
        $this->assertEquals(1048576, $this->logger->getBuffer()[0]['context']['memory_usage']);
        $this->assertEquals(15, $this->logger->getBuffer()[0]['context']['query_count']);
        $this->assertEquals(235, $this->logger->getBuffer()[0]['context']['query_time']);
    }
}
