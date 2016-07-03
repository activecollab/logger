<?php

/*
 * This file is part of the Active Collab Logger.
 *
 * (c) A51 doo <info@activecollab.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ActiveCollab\Logger\ExceptionSerializers;

/**
 * @package ActiveCollab\Logger\Factory\ExceptionSerializers
 */
interface ExceptionSerializersInterface
{
    /**
     * Return an array of exception serializers.
     *
     * @return array
     */
    public function getExceptionSerializers();

    /**
     * Add an exception serializer.
     *
     * When exception serializer is called, three arguments will be provided to the callback:
     *
     * - $argument_name - name of the context argument
     * - $exception - Exception itself
     * - $context - Entire log message context, passed by reference
     *
     * @param  callable $exception_serializer
     * @return $this
     */
    public function &addExceptionSerializer(callable $exception_serializer);
}
