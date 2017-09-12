<?php

namespace Gedmo\Tree\Entity\Repository;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Gedmo\Tree\RepositoryInterface;
use Gedmo\Tree\Strategy;
use Gedmo\Tree\Traits\Repository\ORM\NestedTreeRepositoryTrait;

/**
 * The NestedTreeRepository has some useful functions
 * to interact with NestedSet tree. Repository uses
 * the strategy used by listener
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class NestedTreeRepository extends EntityRepository implements RepositoryInterface
{
    use NestedTreeRepositoryTrait;

    /**
     * {@inheritdoc}
     */
    public function __construct(EntityManager $em, ClassMetadata $class)
    {
        parent::__construct($em, $class);

        $this->initializeTreeRepository($em, $class);
    }
}
