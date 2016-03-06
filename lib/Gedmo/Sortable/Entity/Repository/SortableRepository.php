<?php

namespace Gedmo\Sortable\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Gedmo\Sortable\SortableListener;

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
        $sortableListener = null;
        foreach ($em->getEventManager()->getListeners() as $event => $listeners) {
            foreach ($listeners as $hash => $listener) {
                if ($listener instanceof SortableListener) {
                    $sortableListener = $listener;
                    break;
                }
            }
            if ($sortableListener) {
                break;
            }
        }

        if (null === $sortableListener) {
            throw new \Gedmo\Exception\InvalidMappingException('This repository can be attached only to ORM sortable listener');
        }

        $this->listener = $sortableListener;
        $this->meta = $this->getClassMetadata();
        $this->config = $this->listener->getConfiguration($this->_em, $this->meta->name);
    }

    public function getBySortableGroupsQuery($positionField, array $groupValues = array())
    {
        return $this->getBySortableGroupsQueryBuilder($positionField, $groupValues)->getQuery();
    }

    public function getBySortableGroupsQueryBuilder($positionField, array $groupValues = array())
    {
        $config = $this->config['sortables'][$positionField];

        $groups = isset($config['groups']) ? array_combine(array_values($config['groups']), array_keys($config['groups'])) : array();
        foreach ($groupValues as $name => $value) {
            if (!in_array($name, $config['groups'])) {
                throw new \InvalidArgumentException('Sortable group "'.$name.'" is not defined in Entity '.$this->meta->name);
            }
            unset($groups[$name]);
        }
        if (count($groups) > 0) {
            throw new \InvalidArgumentException(
                'You need to specify values for the following groups to select by sortable groups: '.implode(", ", array_keys($groups)));
        }

        $qb = $this->createQueryBuilder('n');
        $qb->orderBy('n.'.$config['position']);
        $i = 1;
        foreach ($groupValues as $group => $value) {
            $qb->andWhere('n.'.$group.' = :group'.$i)
               ->setParameter('group'.$i, $value);
            $i++;
        }

        return $qb;
    }

    public function getBySortableGroups($positionField, array $groupValues = array())
    {
        $query = $this->getBySortableGroupsQuery($positionField, $groupValues);

        return $query->getResult();
    }

}
