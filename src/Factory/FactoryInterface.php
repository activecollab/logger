<?php

/*
 * This file is part of the Active Collab Logger.
 *
 * (c) A51 doo <info@activecollab.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ActiveCollab\Logger\Factory;

use ActiveCollab\Logger\ExceptionSerializers\ExceptionSerializersInterface;
use ActiveCollab\Logger\LoggerInterface;

/**
 * @package ActiveCollab\Logger
 */
interface FactoryInterface extends ExceptionSerializersInterface
{
    /**
     * Create and configure a new logger instance.
     *
     * @param  string          $app_name
     * @param  string          $app_version
     * @param  string          $app_env
     * @param  int             $log_level
     * @param  string          $logger_type
     * @param  array           $logger_arguments
     * @return LoggerInterface
     */
    public function create($app_name, $app_version, $app_env, $log_level, $logger_type, ...$logger_arguments);

    /**
     * Return a list of additional environment variables.
     *
     * @return array
     */
    public function getAdditionalEvnArguments();

    /**
     * Set a list of environment variables that will be appended to the standard set of environment variables.
     *
     * @param  array $args
     * @return $this
     */
    public function &setAdditionalEnvArguments(array $args);
}
