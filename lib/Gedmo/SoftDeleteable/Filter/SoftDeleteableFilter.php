<?php

namespace Gedmo\SoftDeleteable\Filter;

use Doctrine\ORM\Mapping\ClassMetaData,
    Doctrine\ORM\Query\Filter\SQLFilter,
    Gedmo\SoftDeleteable\SoftDeleteableListener;

/**
 * The SoftDeleteableFilter adds the condition necessary to
 * filter entities which were deleted "softly"
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author Patrik Votoček <patrik@votocek.cz>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

class SoftDeleteableFilter extends SQLFilter
{
    protected $listener;
    protected $entityManager;
    protected $disabled = array();

    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        $class = $targetEntity->getName();
        if (array_key_exists($class, $this->disabled) && $this->disabled[$class] === true) {
            return '';
        } elseif (array_key_exists($targetEntity->rootEntityName, $this->disabled) && $this->disabled[$targetEntity->rootEntityName] === true) {
            return '';
        }

        $exm = $this->getListener()->getConfiguration($this->getEntityManager(), $targetEntity->name);
        if (!$exm || $exm->isEmpty()) {
            return '';
        }

        $conn = $this->getEntityManager()->getConnection();
        $platform = $conn->getDatabasePlatform();
        $column = $targetEntity->getQuotedColumnName($exm->getField()], $platform);

        $addCondSql = $platform->getIsNullExpression($targetTableAlias.'.'.$column);
        if ($exm->timeAware()) {
            $now = $conn->quote(date('Y-m-d H:i:s')); // should use UTC in database and PHP
            $addCondSql = "({$addCondSql} OR {$targetTableAlias}.{$column} > {$now})";
        }
        return $addCondSql;
    }

    public function disableForEntity($class)
    {
        $this->disabled[$class] = true;
    }

    public function enableForEntity($class)
    {
        $this->disabled[$class] = false;
    }

    protected function getListener()
    {
        if ($this->listener === null) {
            foreach ($this->getEntityManager()->getEventManager()->getListeners() as $listeners) {
                foreach ($listeners as $listener) {
                    if ($listener instanceof SoftDeleteableListener) {
                        $this->listener = $listener;
                        break 2;
                    }
                }
            }
            if ($this->listener === null) {
                throw new \RuntimeException('Listener "SoftDeleteableListener" was not added to the EventManager!');
            }
        }
        return $this->listener;
    }

    protected function getEntityManager()
    {
        if ($this->entityManager === null) {
            $refl = new \ReflectionProperty('Doctrine\ORM\Query\Filter\SQLFilter', 'em');
            $refl->setAccessible(true);
            $this->entityManager = $refl->getValue($this);
        }

        return $this->entityManager;
    }
}
