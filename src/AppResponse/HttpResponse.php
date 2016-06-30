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

use Psr\Http\Message\ResponseInterface;

/**
 * @package Angie\AppResponse
 */
class HttpResponse implements AppResponseInterface
{
    /**
     * @var int
     */
    private $status_code;

    /**
     * @var string
     */
    private $reason_phrase;

    /**
     * @param ResponseInterface $response
     */
    public function __construct(ResponseInterface $response)
    {
        $this->status_code = $response->getStatusCode();
        $this->reason_phrase = $response->getReasonPhrase();
    }

    /**
     * {@inheritdoc}
     */
    public function getSummaryArguments()
    {
        $result = [];

        if ($this->status_code) {
            $result['status_code'] = $this->status_code;
        }

        if ($this->reason_phrase) {
            $result['reason_phrase'] = $this->reason_phrase;
        }

        return $result;
    }
}
