<?php

/*
 * This file is part of the Active Collab Logger.
 *
 * (c) A51 doo <info@activecollab.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace ActiveCollab\Logger\Factory;

use ActiveCollab\Logger\ExceptionSerializers\ExceptionSerializersInterface;
use ActiveCollab\Logger\LoggerInterface;
use Monolog\Handler\HandlerInterface;

interface FactoryInterface extends ExceptionSerializersInterface
{
    public function createWithHandlers(
        string $app_name,
        string $app_version,
        string $app_env,
        HandlerInterface ...$handlers
    ): LoggerInterface;

    public function create(
        string $app_name,
        string $app_version,
        string $app_env,
        int $log_level,
        string $logger_type,
        ...$logger_arguments
    ): LoggerInterface;

    /**
     * Return a list of additional environment variables.
     */
    public function getAdditionalEnvArguments(): array;

    /**
     * Set a list of environment variables that will be appended to the standard set of environment variables.
     */
    public function setAdditionalEnvArguments(array $args): FactoryInterface;
}
