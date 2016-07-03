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
class AppEnv implements AppEnvInterface
{
    /**
     * @var string
     */
    private $app_name;

    /**
     * @var string
     */
    private $app_version;

    /**
     * @var string
     */
    private $app_env;

    /**
     * @var array
     */
    private $additional_arguments = [];

    /**
     * AppEnv constructor.
     *
     * @param string $app_name
     * @param string $app_version
     * @param string $app_env
     * @param array  $additional_arguments
     */
    public function __construct($app_name, $app_version, $app_env, $additional_arguments = [])
    {
        $this->app_name = $app_name;
        $this->app_version = $app_version;
        $this->app_env = $app_env;
        $this->additional_arguments = $additional_arguments;
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments()
    {
        return array_merge([
            'app' => $this->app_name,
            'ver' => $this->app_version,
            'env' => $this->app_env,
            'sapi' => php_sapi_name(),
        ], $this->getAdditionalArguments());
    }

    /**
     * {@inheritdoc}
     */
    public function getAdditionalArguments()
    {
        return $this->additional_arguments;
    }

    /**
     * {@inheritdoc}
     */
    public function &setAdditionalArguments(array $args)
    {
        $this->additional_arguments = $args;

        return $this;
    }
}
