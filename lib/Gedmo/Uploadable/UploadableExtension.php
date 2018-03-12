<?php

namespace Gedmo\Uploadable;

use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Uploadable\UploadableListener;
use LaravelDoctrine\Extensions\GedmoExtension;

class UploadableExtension extends GedmoExtension
{
    /**
     * @var UploadableListener
     */
    protected $listener;

    /**
     * @param UploadableListener $listener
     */
    public function __construct(UploadableListener $listener)
    {
        $this->listener = $listener;
    }

    /**
     * @param EventManager           $manager
     * @param EntityManagerInterface $em
     * @param Reader                 $reader
     */
    public function addSubscribers(EventManager $manager, EntityManagerInterface $em, Reader $reader = null)
    {
        $this->addSubscriber($this->listener, $manager, $reader);
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        return [];
    }
}
