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

use Throwable;

/**
 * @package ActiveCollab\Logger\ErrorHandler
 */
interface ErrorHandlerInterface
{
    const SILENCE = 'silence';
    const LOG_ERROR = 'log_error';
    const LOG_NOTICE = 'log_notice';
    const THROW_EXCEPTION = 'exception';

    const ALL_HANDLERS = [self::SILENCE, self::LOG_ERROR, self::LOG_NOTICE, self::THROW_EXCEPTION];

    /**
     * Set error and exception handlers.
     *
     * @return $this
     */
    public function &initialize();

    /**
     * Retore error handlers to the previous handlers.
     *
     * @return $this
     */
    public function &restore();

    /**
     * Return how is error handler configured to handle a particular error.
     *
     * @param  int         $errno
     * @return string|null
     */
    public function getHowToHandleError($errno);

    /**
     * Set how errors of a given type should be handled.
     *
     * @param  int    $errno
     * @param  string $how_to_handle
     * @return $this
     */
    public function &setHowToHandleError($errno, $how_to_handle);

    /**
     * Handle PHP error.
     *
     * @param int    $errno
     * @param string $errstr
     * @param string $errfile
     * @param string $errline
     */
    public function handleError($errno, $errstr, $errfile, $errline);

    /**
     * Return true if error handler rethrows exceptions.
     *
     * @return bool
     */
    public function getReThrowException();

    /**
     * Turn on or off whether error handler should re-throw execeptions that it collects.
     *
     * @param  bool  $value
     * @return $this
     */
    public function &setReThrowException($value);

    /**
     * Handle fatal error.
     *
     * @param Throwable|mixed $error
     */
    public function handleException($error);
}
