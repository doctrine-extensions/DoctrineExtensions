<?php

namespace Gedmo\Translatable\Hydrator\ORM;

use Gedmo\Translatable\TranslatableListener;
use Gedmo\Exception\RuntimeException;
use Doctrine\ORM\Internal\Hydration\SimpleObjectHydrator as BaseSimpleObjectHydrator;

/**
 * If query uses TranslationQueryWalker and is hydrating
 * objects - when it requires this custom object hydrator
 * in order to skip onLoad event from triggering retranslation
 * of the fields
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class SimpleObjectHydrator extends BaseSimpleObjectHydrator
{
    /**
     * 2.1 version
     * {@inheritdoc}
     */
    protected function _hydrateAll()
    {
        $listener = $this->getTranslatableListener();
        $listener->setSkipOnLoad(true);
        $result = parent::_hydrateAll();
        $listener->setSkipOnLoad(false);
        return $result;
    }

    /**
     * 2.2 version
     * {@inheritdoc}
     */
    protected function hydrateAllData()
    {
        $listener = $this->getTranslatableListener();
        $listener->setSkipOnLoad(true);
        $result = parent::hydrateAllData();
        $listener->setSkipOnLoad(false);
        return $result;
    }

    /**
     * Get the currently used TranslatableListener
     *
     * @throws \Gedmo\Exception\RuntimeException - if listener is not found
     * @return TranslatableListener
     */
    protected function getTranslatableListener()
    {
        foreach ($this->_em->getEventManager()->getListeners() as $listeners) {
            foreach ($listeners as $listener) {
                if ($listener instanceof TranslatableListener) {
                    return $listener;
                }
            }
        }
        throw new RuntimeException('The translation listener could not be found');
    }
}
