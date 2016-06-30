<?php

/*
 * This file is part of the Active Collab Logger.
 *
 * (c) A51 doo <info@activecollab.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ActiveCollab\Logger\AppResponse;

/**
 * @package Angie\AppResponse
 */
interface AppResponseInterface
{
    /**
     * Return summary arguments.
     *
     * @return array
     */
    public function getSummaryArguments();
}
