<?php

namespace Gedmo\Tree\Entity\Repository;

use Doctrine\ORM\EntityRepository,
    Doctrine\ORM\EntityManager,
    Doctrine\ORM\Mapping\ClassMetadata,
    Gedmo\Tool\Wrapper\EntityWrapper,
    Gedmo\Tree\RepositoryUtils,
    Gedmo\Tree\RepositoryUtilsInterface,
    Gedmo\Tree\RepositoryInterface;

abstract class AbstractTreeRepository extends EntityRepository implements RepositoryInterface
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
    public function __construct(EntityManager $em, ClassMetadata $class)
    {
        parent::__construct($em, $class);
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
            throw new \Gedmo\Exception\InvalidMappingException('Tree listener was not found on your entity manager, it must be hooked into the event manager');
        }

        $this->listener = $treeListener;
        if (!$this->validate()) {
            throw new \Gedmo\Exception\InvalidMappingException('This repository cannot be used for tree type: ' . $treeListener->getStrategy($em, $class->name)->getName());
        }

        $this->repoUtils = new RepositoryUtils($this->_em, $this->getClassMetadata(), $this->listener, $this);
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
     * @see \Gedmo\Tree\RepositoryUtilsInterface::childrenHierarchy
     */
    public function childrenHierarchy($node = null, $direct = false, array $options = array(), $includeNode = false)
    {
        return $this->repoUtils->childrenHierarchy($node, $direct, $options, $includeNode);
    }

    /**
     * @see \Gedmo\Tree\RepositoryUtilsInterface::buildTree
     */
    public function buildTree(array $nodes, array $options = array())
    {
        return $this->repoUtils->buildTree($nodes, $options);
    }

    /**
     * @see \Gedmo\Tree\RepositoryUtilsInterface::buildTreeArray
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
