<?php

namespace Gedmo\Tree\Mapping\Event\Adapter;

use Gedmo\Mapping\Event\Adapter\ODM as BaseAdapterODM;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo;
use Doctrine\ODM\MongoDB\Cursor;
use Gedmo\Tree\Mapping\Event\TreeAdapter;

/**
 * Doctrine event adapter for ODM adapted
 * for Tree behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo\Tree\Mapping\Event\Adapter
 * @subpackage ODM
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class ODM extends BaseAdapterODM implements TreeAdapter
{
    // Nothing specific yet
}