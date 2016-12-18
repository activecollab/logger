<?php

/*
 * This file is part of the Active Collab Logger.
 *
 * (c) A51 doo <info@activecollab.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ActiveCollab\Logger;

use ActiveCollab\Logger\AppEnv\AppEnvInterface;
use ActiveCollab\Logger\AppRequest\AppRequestInterface;
use ActiveCollab\Logger\AppResponse\AppResponseInterface;
use ActiveCollab\Logger\ExceptionSerializers\ExceptionSerializersInterface;
use Psr\Log\LoggerInterface as PsrLoggerInterface;

/**
 * @package ActiveCollab\Logger
 */
interface LoggerInterface extends PsrLoggerInterface, ExceptionSerializersInterface
{
    const LOG_FOR_PRODUCTION = 200;
    const LOG_FOR_DEBUG = 100;

    const FILE = 'file';
    const GRAYLOG = 'graylog';
    const BLACKHOLE = 'blackhole';
    const TEST = 'test';

    /**
     * Return split string to chunks length, if set.
     *
     * @return int
     */
    public function getSplitStringsInChunks();

    /**
     * Set logger to split long strings in chunks.
     *
     * @param  int   $value
     * @return $this
     */
    public function &setSplitStringsInChunks($value);

    /**
     * Return application environment instance.
     *
     * @return AppEnvInterface
     */
    public function getAppEnv();

    /**
     * Set application environment instance.
     *
     * @param  AppEnvInterface $env
     * @return $this
     */
    public function &setAppEnv(AppEnvInterface $env);

    /**
     * Get request.
     *
     * @return AppRequestInterface
     */
    public function getAppRequest();

    /**
     * Set request.
     *
     * @param  AppRequestInterface $request
     * @return $this
     */
    public function &setAppRequest(AppRequestInterface $request);

    /**
     * Return array of arguments that are extracted from app request.
     *
     * @return array
     */
    public function getAppRequestArguments();

    /**
     * Get app response.
     *
     * @return AppResponseInterface
     */
    public function getAppResponse();

    /**
     * Set app response.
     *
     * @param  AppResponseInterface $response
     * @return $this
     */
    public function &setAppResponse(AppResponseInterface $response);

    /**
     * Return all messages that are buffered in the logger.
     *
     * @return array
     */
    public function getBuffer();

    /**
     * Flush content of the buffer if we have request set.
     *
     * When flush is forced, all messages will be pushed to the handler regardless of request status
     *
     * @param bool $force
     */
    public function flushBuffer($force = false);

    /**
     * Register shutdown function that will flush buffer.
     */
    public function flushBufferOnShutdown();

    /**
     * Log request summary once we shutdown the process.
     *
     * @param float $exec_time_in_s
     * @param int   $memory_usage
     * @param int   $query_count
     * @param float $query_exec_time
     */
    public function requestSummary($exec_time_in_s, $memory_usage, $query_count, $query_exec_time);

    /**
     * Log an event (info with mandatory event attribute).
     *
     * @param  string    $event_name
     * @param  string    $message
     * @param  array     $context
     * @return bool|null
     */
    public function event($event_name, $message, array $context = []);
}
