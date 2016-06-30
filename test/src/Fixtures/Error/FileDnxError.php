<?php

/*
 * This file is part of the Active Collab Logger.
 *
 * (c) A51 doo <info@activecollab.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ActiveCollab\Logger\Test\Fixtures\Error;

/**
 * @package ActiveCollab\Logger\Test\Fixtures\Error
 */
class FileDnxError extends Error
{
    /**
     * Construct the FileDnxError.
     *
     * @param string $file_path
     * @param string $message
     */
    public function __construct($file_path, $message = null)
    {
        if (is_null($message)) {
            $message = "File '$file_path' doesn't exists";
        }

        parent::__construct($message, [
            'path' => $file_path
        ]);
    }
}
