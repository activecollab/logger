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
    'what' => 'something really interesting'
]);
```

## Message Buffering

Logger is built to buffer messages until request details are set (using `setAppRequest()` method). Reason why we delay writing to log is to be able to add request details to all messages, so we can connect the dots alter on:

```php
// Set HTTP request from PSR-7 ServerRequestInterface
$logger->setAppRequest(new \ActiveCollab\Logger\AppRequest\HttpRequest($request));

// Set CLI request from arguments
$logger->setAppRequest(new \ActiveCollab\Logger\AppRequest\CliRequest($_SERVER['argv']));
```

If request is not set, buffer will not be flushed unless you flush it yourself, or register a shutdown function:

```php
$logger->flushBufferOnShutdown();
```
