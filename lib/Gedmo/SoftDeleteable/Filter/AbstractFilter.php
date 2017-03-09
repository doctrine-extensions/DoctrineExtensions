<?php

namespace Gedmo\SoftDeleteable\Filter;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
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
 * @author malc0mn <malc0mn@advalvas.be>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

abstract class AbstractFilter extends SQLFilter
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
     * @var string[bool]
     */
    protected $disabled = array();

    /**
     * @var Connection
     */
    protected $conn;

    /**
     * @var AbstractPlatform
     */
    protected $platform;

    /**
     * @param ClassMetadata $targetEntity
     *
     * @return string|array
     */
    protected function getConfig(ClassMetadata $targetEntity)
    {
        $class = $targetEntity->getName();
        if (array_key_exists($class, $this->disabled) && $this->disabled[$class] === true) {
            return false;
        } elseif (array_key_exists($targetEntity->rootEntityName, $this->disabled) && $this->disabled[$targetEntity->rootEntityName] === true) {
            return false;
        }

        $config = $this->getListener()->getConfiguration($this->getEntityManager(), $targetEntity->name);

        if (!isset($config['softDeleteable']) || !$config['softDeleteable']) {
            return false;
        }

        return $config;
    }

    /**
     * @param array $config
     *
     * @return string
     */
    protected function getColumn(ClassMetadata $targetEntity, array $config)
    {
        $this->conn = $this->getEntityManager()->getConnection();
        $this->platform = $this->conn->getDatabasePlatform();
        return $targetEntity->getQuotedColumnName($config['fieldName'], $this->platform);
    }

    /**
     * @param string $class
     */
    public function disableForEntity($class)
    {
        $this->disabled[$class] = true;
    }

    /**
     * @param string $class
     */
    public function enableForEntity($class)
    {
        $this->disabled[$class] = false;
    }

    /**
     * @return SoftDeleteableListener
     * @throws \RuntimeException
     */
    protected function getListener()
    {
        if ($this->listener === null) {
            $em = $this->getEntityManager();
            $evm = $em->getEventManager();

            foreach ($evm->getListeners() as $listeners) {
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

    /**
     * @return EntityManagerInterface
     */
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
