<?php

namespace Gedmo\Sortable\Entity\Repository;

use Doctrine\ORM\EntityRepository,
    Doctrine\ORM\EntityManager,
    Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Sortable Repository
 *
 * @author Lukas Botsch <lukas.botsch@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class SortableRepository extends EntityRepository
{
    /**
     * Sortable listener on event manager
     *
     * @var SortableListener
     */
    protected $listener = null;

    protected $config = null;
    protected $meta = null;

    public function __construct(EntityManager $em, ClassMetadata $class)
    {
        parent::__construct($em, $class);
        foreach ($em->getEventManager()->getListeners() as $event => $listeners) {
            foreach ($listeners as $hash => $listener) {
                if ($listener instanceof \Gedmo\Sortable\SortableListener) {
                    $this->listener = $listener;
                    break 2;
                }
            }
        }

        if (is_null($this->listener)) {
            throw new \Gedmo\Exception\InvalidMappingException('This repository can be attached only to ORM sortable listener');
        }

        $this->meta = $this->getClassMetadata();
        $this->config = $this->listener->getConfiguration($this->_em, $this->meta->name);
    }

    public function getBySortableGroupsQuery(array $groupValues=array())
    {
        return $this->getBySortableGroupsQueryBuilder($groupValues)->getQuery();
    }

    public function getBySortableGroupsQueryBuilder(array $groupValues=array())
    {
        $groups = array_combine(array_values($this->config['groups']), array_keys($this->config['groups']));
        foreach ($groupValues as $name => $value) {
            if (!in_array($name, $this->config['groups'])) {
                throw new \InvalidArgumentException('Sortable group "'.$name.'" is not defined in Entity '.$this->meta->name);
            }
            unset($groups[$name]);
        }
        if (count($groups) > 0) {
            throw new \InvalidArgumentException(
                'You need to specify values for the following groups to select by sortable groups: '.implode(", ", array_keys($groups)));
        }

        $qb = $this->createQueryBuilder('n');
        $qb->orderBy('n.'.$this->config['position']);
        $i = 1;
        foreach ($groupValues as $group => $value) {
            $qb->andWhere('n.'.$group.' = :group'.$i)
               ->setParameter('group'.$i, $value);
            $i++;
        }
        return $qb;
    }

    public function getBySortableGroups(array $groupValues=array())
    {
        $query = $this->getBySortableGroupsQuery($groupValues);
        return $query->getResult();
    }
}
