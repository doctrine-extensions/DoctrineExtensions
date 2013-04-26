<?php

namespace Gedmo\Sortable\Mapping\Event\Adapter;

use Gedmo\Mapping\Event\Adapter\ODM as BaseAdapterODM;
use Doctrine\ODM\MongoDB\Cursor;
use Gedmo\Sortable\Mapping\Event\SortableAdapter;

/**
 * Doctrine event adapter for ODM adapted
 * for sortable behavior
 *
 * @author Lukas Botsch <lukas.botsch@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class ODM extends BaseAdapterODM implements SortableAdapter
{
    
}
