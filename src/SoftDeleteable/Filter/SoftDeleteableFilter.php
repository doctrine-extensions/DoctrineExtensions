<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\SoftDeleteable\Filter;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Gedmo\SoftDeleteable\SoftDeleteableListener;

/**
 * The SoftDeleteableFilter adds the condition necessary to
 * filter entities which were deleted "softly"
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author Patrik Votoček <patrik@votocek.cz>
 *
 * @final since gedmo/doctrine-extensions 3.11
 */
class SoftDeleteableFilter extends SQLFilter
{
    /**
     * @var SoftDeleteableListener
     */
    protected $listener;

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var array<string, bool>
     * @phpstan-var array<class-string, bool>
     */
    protected $disabled = [];

    /**
     * @param string $targetTableAlias
     *
     * @return string
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        $class = $targetEntity->getName();
        if (true === ($this->disabled[$class] ?? false)) {
            return '';
        }
        if (true === ($this->disabled[$targetEntity->rootEntityName] ?? false)) {
            return '';
        }

        $config = $this->getListener()->getConfiguration($this->getEntityManager(), $targetEntity->name);

        if (!isset($config['softDeleteable']) || !$config['softDeleteable']) {
            return '';
        }

        $platform = $this->getConnection()->getDatabasePlatform();
        $quoteStrategy = $this->getEntityManager()->getConfiguration()->getQuoteStrategy();

        $column = $quoteStrategy->getColumnName($config['fieldName'], $targetEntity, $platform);

        $addCondSql = $platform->getIsNullExpression($targetTableAlias.'.'.$column);
        if (isset($config['timeAware']) && $config['timeAware']) {
            $addCondSql = "({$addCondSql} OR {$targetTableAlias}.{$column} > {$platform->getCurrentTimestampSQL()})";
        }

        return $addCondSql;
    }

    /**
     * @param string $class
     *
     * @phpstan-param class-string $class
     *
     * @return void
     */
    public function disableForEntity($class)
    {
        $this->disabled[$class] = true;
        // Make sure the hash (@see SQLFilter::__toString()) for this filter will be changed to invalidate the query cache.
        $this->setParameter(sprintf('disabled_%s', $class), true);
    }

    /**
     * @param string $class
     *
     * @phpstan-param class-string $class
     *
     * @return void
     */
    public function enableForEntity($class)
    {
        $this->disabled[$class] = false;
        // Make sure the hash (@see SQLFilter::__toString()) for this filter will be changed to invalidate the query cache.
        $this->setParameter(sprintf('disabled_%s', $class), false);
    }

    /**
     * @return SoftDeleteableListener
     *
     * @throws \RuntimeException
     */
    protected function getListener()
    {
        if (null === $this->listener) {
            $em = $this->getEntityManager();
            $evm = $em->getEventManager();

            foreach ($evm->getAllListeners() as $listeners) {
                foreach ($listeners as $listener) {
                    if ($listener instanceof SoftDeleteableListener) {
                        $this->listener = $listener;

                        break 2;
                    }
                }
            }

            if (null === $this->listener) {
                throw new \RuntimeException('Listener "SoftDeleteableListener" was not added to the EventManager!');
            }
        }

        return $this->listener;
    }

    /**
     * @return EntityManagerInterface
     */
    protected function getEntityManager()
    {
        if (null === $this->entityManager) {
            $getEntityManager = \Closure::bind(function (): EntityManagerInterface {
                return $this->em;
            }, $this, parent::class);

            $this->entityManager = $getEntityManager();
        }

        return $this->entityManager;
    }
}
