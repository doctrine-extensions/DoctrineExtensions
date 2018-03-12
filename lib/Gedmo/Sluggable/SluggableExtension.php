<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Sluggable\SluggableListener;
use LaravelDoctrine\Extensions\GedmoExtension;

class SluggableExtension extends GedmoExtension
{
    /**
     * @param EventManager           $manager
     * @param EntityManagerInterface $em
     * @param Reader                 $reader
     */
    public function addSubscribers(EventManager $manager, EntityManagerInterface $em, Reader $reader = null)
    {
        $subscriber = new SluggableListener;

        $this->addSubscriber($subscriber, $manager, $reader);
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        return [];
    }
}
