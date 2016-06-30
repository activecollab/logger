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

use Psr\Http\Message\ServerRequestInterface;

/**
 * @package Angie\AppRequest
 */
class HttpRequest implements AppRequestInterface
{
    /**
     * @var string
     */
    private $uri_path;

    /**
     * @var string
     */
    private $query_string;

    /**
     * @var string
     */
    private $method;

    /**
     * @var string
     */
    private $session_id;

    /**
     * @var string
     */
    private $request_id;

    /**
     * @param ServerRequestInterface $request
     */
    public function __construct(ServerRequestInterface $request)
    {
        $this->uri_path = $request->getUri()->getPath();

        $this->query_string = $this->getQueryString($request);
        $this->method = $request->getMethod();
        $this->session_id = (string) $request->getAttribute('session_id');
        $this->request_id = (string) $request->getAttribute('request_id');
    }

    /**
     * Clean up and return query string from request.
     *
     * @param  ServerRequestInterface $request
     * @return string
     */
    private function getQueryString(ServerRequestInterface $request)
    {
        $query_params = $request->getQueryParams();

        foreach (['path_info', 'api_version'] as $param_to_unset) {
            if (isset($query_params[$param_to_unset])) {
                unset($query_params[$param_to_unset]);
            }
        }

        return http_build_query($query_params, '', '&');
    }

    /**
     * {@inheritdoc}
     */
    public function getSessionId()
    {
        return $this->session_id;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequestId()
    {
        return $this->request_id;
    }

    /**
     * {@inheritdoc}
     */
    public function getSummaryArguments()
    {
        return ['uri' => $this->uri_path, 'method' => $this->method, 'query_string' => $this->query_string];
    }

    /**
     * {@inheritdoc}
     */
    public function getSignature()
    {
        $signature = "$this->method $this->uri_path";

        if ($this->query_string) {
            if (mb_strlen($this->query_string) > 45) {
                $signature .= '?' . substr($this->query_string, 0, 45) . '...';
            } else {
                $signature .= "?$this->query_string";
            }
        }

        return $signature;
    }
}
