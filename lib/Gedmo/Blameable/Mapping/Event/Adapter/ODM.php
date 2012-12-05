<?php

namespace Gedmo\Blameable\Mapping\Event\Adapter;

use Gedmo\Mapping\Event\Adapter\ODM as BaseAdapterODM;
use Gedmo\Blameable\Mapping\Event\BlameableAdapter;

/**
 * Doctrine event adapter for ODM adapted
 * for Blameable behavior. Simple version to manually inject username to use.
 *
 * @author David Buchmann <mail@davidbu.ch>
 * @package Gedmo\Blameable\Mapping\Event\Adapter
 * @subpackage ODM
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class ODM extends BaseAdapterODM implements BlameableAdapter
{
    private $user;

    /**
     * {@inheritDoc}
     */
    public function getUserValue($meta, $field)
    {
        return $this->user;
    }

    /**
     * {@inheritDoc}
     */
    public function setUserValue($user)
    {
        $this->user = $user;
    }
}