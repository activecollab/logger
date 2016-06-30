<?php

/*
 * This file is part of the Active Collab Logger.
 *
 * (c) A51 doo <info@activecollab.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ActiveCollab\Logger\Test\Base;

/**
 * @package ActiveCollab\Memories\Test
 */
abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @return string
     */
    protected function getTestDir()
    {
        return TEST_DIR;
    }
}
