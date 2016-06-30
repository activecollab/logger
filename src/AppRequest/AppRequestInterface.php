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

/**
 * @package Angie\AppRequest
 */
interface AppRequestInterface
{
    /**
     * Return session ID.
     *
     * @return string
     */
    public function getSessionId();

    /**
     * Return request ID.
     *
     * @return string
     */
    public function getRequestId();

    /**
     * Return summary arguments.
     *
     * @return array
     */
    public function getSummaryArguments();

    /**
     * Return request signature.
     *
     * @return string
     */
    public function getSignature();
}
