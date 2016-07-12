<?php

/*
 * This file is part of the Active Collab Logger.
 *
 * (c) A51 doo <info@activecollab.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ActiveCollab\Logger\ErrorHandler;

use ErrorException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use InvalidArgumentException;

/**
 * @package ActiveCollab\Logger\ErrorHandler
 */
class ErrorHandler implements ErrorHandlerInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Definition of how specific error types should be handled.
     *
     * @var array
     */
    private $how_to_handle_error = [
        E_ERROR => self::THROW_EXCEPTION,
        E_WARNING => self::LOG_ERROR,
        E_NOTICE => self::LOG_ERROR,
        E_STRICT => self::LOG_NOTICE,
        E_PARSE => self::THROW_EXCEPTION,
        E_DEPRECATED => self::LOG_ERROR,
        E_CORE_ERROR => self::THROW_EXCEPTION,
        E_CORE_WARNING => self::LOG_ERROR,
        E_COMPILE_ERROR => self::THROW_EXCEPTION,
        E_COMPILE_WARNING => self::LOG_ERROR,
        E_USER_ERROR => self::LOG_ERROR,
        E_USER_WARNING => self::LOG_ERROR,
        E_USER_NOTICE => self::LOG_ERROR,
        E_USER_DEPRECATED => self::LOG_ERROR,
        E_RECOVERABLE_ERROR => self::LOG_ERROR,
        E_ALL => self::LOG_ERROR,
    ];

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface &$logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function &initialize()
    {
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function &restore()
    {
        restore_error_handler();
        restore_exception_handler();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getHowToHandleError($errno)
    {
        return isset($this->how_to_handle_error[$errno]) && $this->how_to_handle_error[$errno] ? $this->how_to_handle_error[$errno] : null;
    }

    /**
     * Set how errors of a given type should be handled.
     *
     * @param  int    $errno
     * @param  string $how_to_handle
     * @return $this
     */
    public function &setHowToHandleError($errno, $how_to_handle)
    {
        if (!in_array($how_to_handle, self::ALL_HANDLERS)) {
            throw new InvalidArgumentException('Invalid error handler');
        }

        $this->how_to_handle_error[$errno] = $how_to_handle;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function handleError($errno, $errstr, $errfile, $errline)
    {
        $context = ['message' => $errstr, 'code' => $errno, 'file' => $errfile, 'line' => $errline, 'trace' => (new RuntimeException('Getting trace'))->getTraceAsString()];

        switch ($this->getHowToHandleError($errno)) {
            case self::SILENCE:
                break;
            case self::LOG_ERROR:
                $this->logger->error('Error: {message}', $context);

                break;
            case self::LOG_NOTICE:
                $this->logger->notice('Strict standards: {message}', $context);

                break;
            default:
                throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
        }
    }

    /**
     * @var bool
     */
    private $re_throw_exception = true;

    /**
     * {@inheritdoc}
     */
    public function getReThrowException()
    {
        return $this->re_throw_exception;
    }

    /**
     * {@inheritdoc}
     */
    public function &setReThrowException($value)
    {
        $this->re_throw_exception = (bool) $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function handleException($error)
    {
        $this->logger->critical('Unhandled exception: {message}', [
            'class' => get_class($error),
            'message' => $error->getMessage(),
            'exception' => $error,
        ]);

        if ($this->getReThrowException()) {
            throw $error;
        }
    }
}
