<?php

namespace Gedmo\Tree\Document\MongoDB\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository,
    Doctrine\ODM\MongoDB\DocumentManager,
    Doctrine\ODM\MongoDB\Mapping\ClassMetadata,
    Doctrine\ODM\MongoDB\UnitOfWork,
    Gedmo\Tree\RepositoryUtils,
    Gedmo\Tree\RepositoryUtilsInterface,
    Gedmo\Tree\RepositoryInterface;

abstract class AbstractTreeRepository extends DocumentRepository implements RepositoryInterface
{
    /**
     * Tree listener on event manager
     *
     * @var AbstractTreeListener
     */
    protected $listener = null;

    /**
     * Repository utils
     */
    protected $repoUtils = null;

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

        $this->repoUtils = new RepositoryUtils($this->dm, $this->getClassMetadata(), $this->listener, $this);
    }

    /**
     * Sets the RepositoryUtilsInterface instance
     *
     * @param \Gedmo\Tree\RepositoryUtilsInterface $repoUtils
     *
     * @return $this
     */
    public function setRepoUtils(RepositoryUtilsInterface $repoUtils)
    {
        $this->repoUtils = $repoUtils;

        return $this;
    }

    /**
     * Returns the RepositoryUtilsInterface instance
     *
     * @return \Gedmo\Tree\RepositoryUtilsInterface|null
     */
    public function getRepoUtils()
    {
        return $this->repoUtils;
    }

    /**
     * {@inheritDoc}
     */
    public function childrenHierarchy($node = null, $direct = false, array $options = array())
    {
        return $this->repoUtils->childrenHierarchy($node, $direct, $options);
    }

    /**
     * {@inheritDoc}
     */
    public function buildTree(array $nodes, array $options = array())
    {
        return $this->repoUtils->buildTree($nodes, $options);
    }

    /**
     * {@inheritDoc}
     */
    public function buildTreeArray(array $nodes)
    {
        return $this->repoUtils->buildTreeArray($nodes);
    }

    /**
     * Checks if current repository is right
     * for currently used tree strategy
     *
     * @return bool
     */
    abstract protected function validate();
}