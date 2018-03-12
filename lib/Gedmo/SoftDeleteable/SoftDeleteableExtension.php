<?php

namespace Gedmo\SoftDeleteable;

use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManagerInterface;
use Gedmo\SoftDeleteable\Filter\SoftDeleteableFilter;
use Gedmo\SoftDeleteable\SoftDeleteableListener;
use LaravelDoctrine\Extensions\GedmoExtension;

class SoftDeleteableExtension extends GedmoExtension
{
    /**
     * @param EventManager           $manager
     * @param EntityManagerInterface $em
     * @param Reader                 $reader
     */
    public function addSubscribers(EventManager $manager, EntityManagerInterface $em, Reader $reader = null)
    {
        $subscriber = new SoftDeleteableListener();

        $this->addSubscriber($subscriber, $manager, $reader);
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        return [
            'soft-deleteable' => SoftDeleteableFilter::class
        ];
    }
}
