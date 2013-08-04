<?php

namespace Gedmo\Sortable\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Gedmo\Sortable\SortableListener;
use Gedmo\Exception\InvalidMappingException;
use Gedmo\Mapping\ObjectManagerHelper as OMH;
use Gedmo\Exception\InvalidArgumentException;

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
    protected $listener;

    /**
     * @var \Gedmo\Sortable\Mapping\SortableMetadata
     */
    protected $exm;

    public function __construct(EntityManager $em, ClassMetadata $class)
    {
        parent::__construct($em, $class);
        foreach ($em->getEventManager()->getListeners() as $event => $listeners) {
            foreach ($listeners as $hash => $listener) {
                if ($listener instanceof SortableListener) {
                    $this->listener = $listener;
                    break 2;
                }
            }
        }

        if (is_null($this->listener)) {
            throw new InvalidMappingException('This repository can be attached only to ORM sortable listener');
        }

        $this->exm = $this->listener->getConfiguration($em, $class->name);
    }

    public function getBySortableGroupsQuery($sortableField, array $groupValues = array())
    {
        return $this->getBySortableGroupsQueryBuilder($sortableField, $groupValues)->getQuery();
    }

    public function getBySortableGroupsQueryBuilder($sortableField, array $groupValues = array())
    {
        if (!$options = $this->exm->getOptions($sortableField)) {
            throw new InvalidArgumentException("Sortable field: '$sortableField' is not configured to be sortable in class: {$this->_class->name}");
        }

        foreach ($groupValues as $name => $value) {
            if (!in_array($name, $options['groups'])) {
                throw new InvalidArgumentException('Sortable group "'.$name.'" is not defined in class '.$this->_class->name);
            }
        }

        $qb = $this->_em->createQueryBuilder()
            ->select('n')
            ->from($options['rootClass'], 'n')
            ->orderBy('n.'.$sortableField);
        $i = 1;
        foreach ($groupValues as $group => $value) {
            $whereFunc = is_null($qb->getDQLPart('where')) ? 'where' : 'andWhere';
            if (is_null($value)) {
                $qb->{$whereFunc}($qb->expr()->isNull('n.'.$group));
            } else {
                $qb->{$whereFunc}('n.'.$group.' = :group__'.$i);
                $qb->setParameter('group__'.($i++), $value);
            }
        }
        return $qb;
    }

    public function getBySortableGroups($sortableField, array $groupValues = array())
    {
        return $this->getBySortableGroupsQuery($sortableField, $groupValues)->getResult();
    }
}
