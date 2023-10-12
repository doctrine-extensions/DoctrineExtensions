<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Sortable\Entity\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Gedmo\Exception\InvalidMappingException;
use Gedmo\Sortable\SortableListener;

/**
 * Sortable Repository
 *
 * @author Lukas Botsch <lukas.botsch@gmail.com>
 *
 * @phpstan-extends EntityRepository<object>
 */
class SortableRepository extends EntityRepository
{
    /**
     * Sortable listener on event manager
     *
     * @var SortableListener
     */
    protected $listener;

    /**
     * @var array<string, mixed>
     */
    protected $config;

    /**
     * @var ClassMetadata
     */
    protected $meta;

    public function __construct(EntityManagerInterface $em, ClassMetadata $class)
    {
        parent::__construct($em, $class);
        $sortableListener = null;
        foreach ($em->getEventManager()->getAllListeners() as $listeners) {
            foreach ($listeners as $listener) {
                if ($listener instanceof SortableListener) {
                    $sortableListener = $listener;

                    break 2;
                }
            }
        }

        if (null === $sortableListener) {
            throw new InvalidMappingException('This repository can be attached only to ORM sortable listener');
        }

        $this->listener = $sortableListener;
        $this->meta = $this->getClassMetadata();
        $this->config = $this->listener->getConfiguration($this->getEntityManager(), $this->meta->getName());
    }

    /**
     * @param array<string, mixed> $groupValues
     *
     * @return Query
     */
    public function getBySortableGroupsQuery(array $groupValues = [])
    {
        return $this->getBySortableGroupsQueryBuilder($groupValues)->getQuery();
    }

    /**
     * @param array<string, mixed> $groupValues
     *
     * @return QueryBuilder
     */
    public function getBySortableGroupsQueryBuilder(array $groupValues = [])
    {
        $groups = isset($this->config['groups']) ? array_combine(array_values($this->config['groups']), array_keys($this->config['groups'])) : [];
        foreach ($groupValues as $name => $value) {
            if (!in_array($name, $this->config['groups'], true)) {
                throw new \InvalidArgumentException('Sortable group "'.$name.'" is not defined in Entity '.$this->meta->getName());
            }
            unset($groups[$name]);
        }
        if ([] !== $groups) {
            throw new \InvalidArgumentException('You need to specify values for the following groups to select by sortable groups: '.implode(', ', array_keys($groups)));
        }

        $qb = $this->createQueryBuilder('n');
        $qb->orderBy('n.'.$this->config['position']);
        $i = 1;
        foreach ($groupValues as $group => $value) {
            $qb->andWhere('n.'.$group.' = :group'.$i)
               ->setParameter('group'.$i, $value);
            ++$i;
        }

        return $qb;
    }

    /**
     * @param array<string, mixed> $groupValues
     *
     * @return array<int, object>
     */
    public function getBySortableGroups(array $groupValues = [])
    {
        $query = $this->getBySortableGroupsQuery($groupValues);

        return $query->getResult();
    }
}
