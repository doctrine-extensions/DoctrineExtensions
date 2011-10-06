<?php

namespace Gedmo\Translatable\Hydrator\ORM;

use Gedmo\Translatable\Query\TreeWalker\TranslationWalker;
use Doctrine\ORM\Internal\Hydration\ObjectHydrator as BaseObjectHydrator;

/**
 * If query uses TranslationQueryWalker and is hydrating
 * objects - when it requires this custom object hydrator
 * in order to skip onLoad event from triggering retranslation
 * of the fields
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Translatable.Hydrator.ORM
 * @subpackage ObjectHydrator
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class ObjectHydrator extends BaseObjectHydrator
{
    /**
     * {@inheritdoc}
     */
    protected function _hydrateAll()
    {
        $listener = $this->_hints[TranslationWalker::HINT_TRANSLATION_LISTENER];
        $listener->setSkipOnLoad(true);
        $result = parent::_hydrateAll();
        $listener->setSkipOnLoad(false);
        return $result;
    }
}