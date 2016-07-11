# Logger

[![Build Status](https://travis-ci.org/activecollab/logger.svg?branch=master)](https://travis-ci.org/activecollab/logger)

This pakcage implements some of our internal conventions on top of PSR-3. Logger that it publishes is fully PSR-3 comptabile with some extra functionality (optional), as well as a factory that makes logger creation easy:
 
```php
$factory = new LoggerFactory();
$logger = $factory->create('Active Collab', '1.0.0', 'development', LoggerInterface::LOG_FOR_DEBUG, LoggerInterface::FILE, '/path/to/logs/dir');
```

Once logger is set, you can use it like any other PSR-3 logger:

```php
$logger->info('Something interesting happened: {what}', [
    'what' => 'really interesting event'
]);
```

Special loggers are:

1. Event logging using `LoggerInterface::event()` method. This will log a named event on info level, and set event context attribute to event name,
1. Request summary logging using `LoggerInterface::requestSummary()` method. This will log some interesting request data, like executing time, total queries and query count etc.

## Loggers

Packages comes with following backends implemented:

`LoggerInterface::FILE` - Log to files in log directory. Log directory is required as first logger argument when creating a logger:

```php
$logger = $factory->create('Active Collab', '1.0.0', 'development', LoggerInterface::LOG_FOR_DEBUG, LoggerInterface::FILE, '/path/to/logs/dir', 'my-awesome-logs.txt', 0777);
```

Second argument is log file name, and it is optional. When skipped, system will log to `log.txt` file in the specified folder.
Third argument is file permissions level. Default is 0644 when skipped, but you can specify any value (in octal notation).

Note that we set rotating file logging, where only past 7 days of logs are kept.

`LoggerInterface::GRAYLOG` - Log messages are sent to Graylog2 server using GELF formatter. Additional arguments are Graylog2 server host and port. If they are skipped, 127.0.0.1 and are used:

```php
$logger = $factory->create('Active Collab', '1.0.0', 'development', LoggerInterface::LOG_FOR_DEBUG, LoggerInterface::GRAYLOG, 'graylog.company.com', 12201);
```

`LoggerInterface::BLACKHOLE` - Messages are not logged anywhere.

## Message Buffering

Logger is built to buffer messages until request details are set (using `setAppRequest()` method). Reason why we delay writing to log is to be able to add request details to all messages, so we can connect the dots alter on:

```php
// Set HTTP request from PSR-7 ServerRequestInterface
$logger->setAppRequest(new \ActiveCollab\Logger\AppRequest\HttpRequest($request));

// Set CLI request from arguments
$logger->setAppRequest(new \ActiveCollab\Logger\AppRequest\CliRequest('session ID', $_SERVER['argv']));
```

If request is not set, buffer will not be flushed unless you flush it yourself, or register a shutdown function:

```php
$logger->flushBufferOnShutdown();
```

## Application Details

This package always logs application name, version and environemnt. These arguments are required and they need to be provided to `FactoryInterface::create()` method, when creating new logger instance:

```php
$logger = $factory->create('Active Collab', '1.0.0', 'development', LoggerInterface::LOG_FOR_DEBUG, LoggerInterface::FILE, '/path/to/logs/dir');
```

Environment arguments are sent as context arguments with all messages captured via logger instance. User can specify additional environment arguments, using `FactoryInterface::setAdditionalEnvArguments()` method:

```php
// Additional environment arguments can be set on factory level, and factory will pass them to all loggers that it produces
$factory->setAdditionalEnvArguments([
    'account_id' => 123,
    'extra_argument' => 'with extra value',
]);

// Or you can specify them on logger level
$logger->setAdditionalEnvArguments([
    'account_id' => 123,
    'extra_argument' => 'with extra value',
]);
```

## Exception Serialization

When exceptions are passed as context arguments, package will "explode" them to a group of relevant arguments: message, file, line, code, and trace. Previous exception is also extracted, when available:

```php
try {
    // Something risky
} catch (ExceptioN $e) {
    $logger->error('An {exception} happened :(', [
        'exception' => $e,
    ]);
}
```

If you have special exceptions that collect more info than message, code, file, line, trace and previous, you can register a callback that will extract that data as well:

```php
$logger->addExceptionSerializer(function ($argument_name, $exception, array &$context) {
    if ($exception instanceof \SpecialError) {
        foreach ($exception->getParams() as $k => $v) {
            $context["{$argument_name}_extra_param_{$k}"] = $v;
        }
    }
});
```

Callback gets three arguments:

1. `$argument_name` - contenxt argument name under which we found the exception,
1. `$exception_name` - exception itself,
1. `$context` - access to resulting log message context arguments.

As with additional environment variables, exception serializers can be added to factory, and factory will pass it on to all loggers that it produces:

```php
$factory->addExceptionSerializer(function ($argument_name, $exception, array &$context) {
    if ($exception instanceof \SpecialError) {
        foreach ($exception->getParams() as $k => $v) {
            $context["{$argument_name}_extra_param_{$k}"] = $v;
        }
    }
});
```

## Error Handling

Logger comes equiped with a class that can register error and exception handlers and direct them to the log. Quick setup:

```php
$handler = new ErrorHandler($logger);
$handler->initialize();
```

To restore error and exception handled, simply call `restore()` method:

```php
$handler->restore();
```

Handler can be configured to do different things for diffent error levels. For example, you can configure it to throw an exception on PHP warning, or to silence an event all together:

```php
$handler->setHowToHandleError(E_STRICT, ErrorHandlerInterface::SILENCE);
$handler->setHowToHandleError(E_DEPRECATED, ErrorHandlerInterface::LOG_NOTICE);
$handler->setHowToHandleError(E_NOTICE, ErrorHandlerInterface::LOG_ERROR);
$handler->setHowToHandleError(E_USER_ERROR, ErrorHandlerInterface::THROW_EXCEPTION);
```

By default, exceptions are logged and re-thrown. This behavior can be turned off:

```php
$handler->setReThrowException(false);
```
