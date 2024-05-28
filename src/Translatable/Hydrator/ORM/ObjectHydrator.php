<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Translatable\Hydrator\ORM;

use Doctrine\ORM\Internal\Hydration\ObjectHydrator as BaseObjectHydrator;
use Gedmo\Exception\RuntimeException;
use Gedmo\Tool\ORM\Hydration\EntityManagerRetriever;
use Gedmo\Tool\ORM\Hydration\HydratorCompat;
use Gedmo\Translatable\TranslatableListener;

/**
 * If query uses TranslationQueryWalker and is hydrating
 * objects - when it requires this custom object hydrator
 * in order to skip onLoad event from triggering retranslation
 * of the fields
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @final since gedmo/doctrine-extensions 3.11
 */
class ObjectHydrator extends BaseObjectHydrator
{
    use EntityManagerRetriever;
    use HydratorCompat;

    /**
     * State of skipOnLoad for listener between hydrations
     *
     * @see ObjectHydrator::prepare()
     * @see ObjectHydrator::cleanup()
     */
    private ?bool $savedSkipOnLoad = null;

    protected function doPrepareWithCompat(): void
    {
        $listener = $this->getTranslatableListener();
        $this->savedSkipOnLoad = $listener->isSkipOnLoad();
        $listener->setSkipOnLoad(true);
        parent::prepare();
    }

    protected function doCleanupWithCompat(): void
    {
        parent::cleanup();
        $listener = $this->getTranslatableListener();
        $listener->setSkipOnLoad($this->savedSkipOnLoad ?? false);
    }

    /**
     * Get the currently used TranslatableListener
     *
     * @throws RuntimeException if listener is not found
     *
     * @return TranslatableListener
     */
    protected function getTranslatableListener()
    {
        foreach ($this->getEntityManager()->getEventManager()->getAllListeners() as $listeners) {
            foreach ($listeners as $listener) {
                if ($listener instanceof TranslatableListener) {
                    return $listener;
                }
            }
        }

        throw new RuntimeException('The translation listener could not be found');
    }
}
