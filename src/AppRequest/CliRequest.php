<?php

/*
 * This file is part of the Active Collab Logger.
 *
 * (c) A51 doo <info@activecollab.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ActiveCollab\Logger\AppRequest;

use RuntimeException;

/**
 * @package Angie\AppRequest
 */
class CliRequest implements AppRequestInterface
{
    /**
     * @var string
     */
    private $instance_id;

    /**
     * @var array
     */
    private $all_command_arguments;

    /**
     * @var string
     */
    private $command;

    /**
     * @var array
     */
    private $command_arguments;

    /**
     * @var int
     */
    private $timestamp;

    /**
     * @param int   $instance_id
     * @param array $all_command_arguments
     */
    public function __construct($instance_id, $all_command_arguments)
    {
        $this->instance_id = (string) $instance_id;
        $this->all_command_arguments = array_values($all_command_arguments);

        $command_index_at = null;

        foreach ($this->all_command_arguments as $k => $v) {
            if ($this->strEndsWith($v, '.php')) {
                $command_index_at = $this->strEndsWith($v, 'activecollab-cli.php') ? $k + 1 : $k;
                break;
            }
        }

        if ($command_index_at === null) {
            throw new RuntimeException("Command '" . implode(' ', $all_command_arguments) . "' is not an Active Collab CLI command");
        }

        $this->command = $this->all_command_arguments[$command_index_at];
        $this->command_arguments = implode(' ', array_map(function ($command_argument) {
            return strpos($command_argument, ' ') === false ? $command_argument : escapeshellarg($command_argument);
        }, array_splice($this->all_command_arguments, $command_index_at + 1)));

        $this->timestamp = time();
    }

    /**
     * {@inheritdoc}
     */
    public function getSessionId()
    {
        return $this->instance_id;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequestId()
    {
        return "$this->command/$this->timestamp";
    }

    /**
     * {@inheritdoc}
     */
    public function getSummaryArguments()
    {
        return ['command_arguments' => $this->command_arguments];
    }

    /**
     * {@inheritdoc}
     */
    public function getSignature()
    {
        $signature = "~$this->command";

        if ($this->command_arguments) {
            if (mb_strlen($this->command_arguments) > 45) {
                $signature .= ' ' . substr($this->command_arguments, 0, 45) . '...';
            } else {
                $signature .= " $this->command_arguments";
            }
        }

        return $signature;
    }

    /**
     * Return TRUE if $stirng ends with $needle.
     *
     * @param  string $string
     * @param  string $needle
     * @return bool
     */
    private function strEndsWith($string, $needle)
    {
        $strlen = strlen($string);
        $testlen = strlen($needle);

        if ($testlen > $strlen) {
            return false;
        }

        return substr_compare($string, $needle, $strlen - $testlen, $testlen) === 0;
    }
}
