<?php

namespace Gedmo\Sortable\Document\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository,
    Doctrine\ODM\MongoDB\DocumentManager,
    Doctrine\ODM\MongoDB\UnitOfWork,
    Doctrine\ODM\MongoDB\Mapping\ClassMetadata,
    Gedmo\Sortable\ODM\MongoDB\SortableListener;

/**
 * The NestedTreeRepository has some useful functions
 * to interact with NestedSet tree. Repository uses
 * the strategy used by listener
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Tree.Entity.Repository
 * @subpackage NestedTreeRepository
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class SortableRepository extends DocumentRepository
{
    /**
     * Tree listener on event manager
     *
     * @var \Gedmo\Sortable\AbstractSortableListener
     */
    protected $listener = null;

    /**
     * {@inheritdoc}
     */
    public function __construct(DocumentManager $dm, UnitOfWork $uow, ClassMetadata $class)
    {
        parent::__construct($dm, $uow, $class);
        
        $evm = $dm->getEventManager();
        $sortableListener = null;
        foreach ($evm->getListeners() as $listeners) {
            foreach ($listeners as $listener) {
                if ($listener instanceof SortableListener) {
                    $sortableListener = $listener;
                    break;
                }
            }
        }

        if (is_null($sortableListener)) {
            throw new \Gedmo\Exception\InvalidMappingException('This repository can be attached only to ODM listener');
        }

        $this->listener = $sortableListener;
    }

    /**
     * Move the node down in the same level
     *
     * @param object $node
     * @param mixed $number
     *         integer - number of positions to shift
     *         boolean - if "true" - shift till last position
     * @throws RuntimeException - if something fails in transaction
     * @return boolean - true if shifted
     */
    public function setSort($sortable, $sort)
    {
        $meta = $this->getClassMetadata();
        if ($sortable instanceof $meta->rootDocumentName) {
            
            $config = $this->listener->getConfiguration($this->dm, $meta->name);
            $nextSiblings = $this->listener->getAllSortableAfterObject($sortable, $this->dm);

            foreach ($nextSiblings as $nextSibling) {
                $sibSort = $meta->getReflectionProperty($config['sort'])->getValue($nextSibling);
                
                if ($sibSort > $sort) {
                    break;
                }

                $this->listener->updateSortableSort($this->dm, $nextSibling, $sibSort - 1);
            }

            $this->listener->updateSortableSort($this->dm, $sortable, $sort);
        } else {
            throw \Gedmo\Exception\InvalidArgumentException("Node is not related to this repository");
        }
    }
}
