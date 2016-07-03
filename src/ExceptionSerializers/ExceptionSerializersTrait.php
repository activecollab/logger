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
trait ExceptionSerializersTrait
{
    /**
     * @var callable[]
     */
    private $exception_serializers = [];

    /**
     * {@inheritdoc}
     */
    public function getExceptionSerializers()
    {
        return $this->exception_serializers;
    }

    /**
     * {@inheritdoc}
     */
    public function &addExceptionSerializer(callable $exception_serializer)
    {
        $this->exception_serializers[] = $exception_serializer;

        return $this;
    }
}
