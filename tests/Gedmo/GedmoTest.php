<?php

namespace Gedmo\Tree;

use Gedmo\Gedmo;

/**
 * These are tests for Gedmo class
 *
 * @author Alexander Schranz <alexander.schranz@sulu.io>
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class GedmoTest extends \PHPUnit\Framework\TestCase
{
    public function testGetRootPath()
    {
        $this->assertSame(
            realpath(dirname(__DIR__, 2) . '/src'),
            Gedmo::getRootPath()
        );
    }
}
