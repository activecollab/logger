<?php

/*
 * This file is part of the Active Collab Logger.
 *
 * (c) A51 doo <info@activecollab.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ActiveCollab\Logger\AppEnv;

/**
 * @package ActiveCollab\Logger\AppEnv
 */
interface AppEnvInterface
{
    /**
     * Return a list of environment arguments.
     *
     * @return array
     */
    public function getArguments();

    /**
     * Return a list of additional environment variables.
     *
     * @return array
     */
    public function getAdditionalArguments();

    /**
     * Set a list of environment variables that will be appended to the standard set of environment variables.
     *
     * @param  array $args
     * @return $this
     */
    public function &setAdditionalArguments(array $args);
}
