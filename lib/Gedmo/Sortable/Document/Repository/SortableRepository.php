<?php

namespace Gedmo\Sortable\Document\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Gedmo\Sortable\SortableListener;
use Gedmo\Exception\InvalidMappingException;
use Gedmo\Mapping\ObjectManagerHelper as OMH;

/**
 * Sortable Repository
 *
 * @author Lukas Botsch <lukas.botsch@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class SortableRepository extends DocumentRepository
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

    public function __construct(DocumentManager $dm, ClassMetadata $class)
    {
        parent::__construct($dm, $class);
        foreach ($dm->getEventManager()->getListeners() as $event => $listeners) {
            foreach ($listeners as $hash => $listener) {
                if ($listener instanceof SortableListener) {
                    $this->listener = $listener;
                    break 2;
                }
            }
        }

        if (is_null($this->listener)) {
            throw new InvalidMappingException('Sortable listener is not hooked to DocumentManager of this repository for class: '.$class->name);
        }
        $this->exm = $this->listener->getConfiguration($dm, $class->name);
    }

    public function getBySortableGroupsQuery($sortableField, array $groupValues = array())
    {
        return $this->getBySortableGroupsQueryBuilder($sortableField, $groupValues)->getQuery();
    }

    public function getBySortableGroupsQueryBuilder($sortableField, array $groupValues = array())
    {
        if (!$options = $this->exm->getOptions($sortableField)) {
            throw new InvalidArgumentException("Sortable field: '$sortableField' is not configured to be sortable in class: {$this->class->name}");
        }

        foreach ($groupValues as $name => $value) {
            if (!in_array($name, $options['groups'])) {
                throw new InvalidArgumentException('Sortable group "'.$name.'" is not defined in class '.$this->class->name);
            }
        }

        $qb = $this->dm->createQueryBuilder($options['rootClass']);
        $qb->sort($sortable, 'asc');
        foreach ($groupValues as $group => $value) {
            if ($this->class->isSingleValuedAssociation($group) && null !== $value) {
                $id = OMH::getIdentifier($this->dm, $value);
                $qb->field($group . '.$id')->equals(new \MongoId($id));
            } else {
                $qb->field($group)->equals($value);
            }
        }
        return $qb;
    }

    public function getBySortableGroups($sortableField, array $groupValues = array())
    {
        return $this->getBySortableGroupsQuery($sortableField, $groupValues)->execute();
    }
}
