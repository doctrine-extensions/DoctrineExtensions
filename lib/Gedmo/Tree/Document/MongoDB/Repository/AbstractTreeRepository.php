<?php

namespace Gedmo\Tree\Document\MongoDB\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository,
    Doctrine\ODM\MongoDB\DocumentManager,
    Doctrine\ODM\MongoDB\Mapping\ClassMetadata,
    Doctrine\ODM\MongoDB\UnitOfWork;

abstract class AbstractTreeRepository extends DocumentRepository
{
    /**
     * Tree listener on event manager
     *
     * @var AbstractTreeListener
     */
    protected $listener = null;

    /**
     * {@inheritdoc}
     */
    public function __construct(DocumentManager $em, UnitOfWork $uow, ClassMetadata $class)
    {
        parent::__construct($em, $uow, $class);
        $treeListener = null;
        foreach ($em->getEventManager()->getListeners() as $listeners) {
            foreach ($listeners as $listener) {
                if ($listener instanceof \Gedmo\Tree\TreeListener) {
                    $treeListener = $listener;
                    break;
                }
            }
            if ($treeListener) {
                break;
            }
        }

        if (is_null($treeListener)) {
            throw new \Gedmo\Exception\InvalidMappingException('This repository can be attached only to ODM MongoDB tree listener');
        }

        $this->listener = $treeListener;
        if (!$this->validate()) {
            throw new \Gedmo\Exception\InvalidMappingException('This repository cannot be used for tree type: ' . $treeListener->getStrategy($em, $class->name)->getName());
        }
    }

    /**
     * Checks if current repository is right
     * for currently used tree strategy
     *
     * @return bool
     */
    abstract protected function validate();
}