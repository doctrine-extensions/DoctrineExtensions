<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\SoftDeleteable\Filter\ODM;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Query\Filter\BsonFilter;
use Gedmo\SoftDeleteable\SoftDeleteableListener;

class SoftDeleteableFilter extends BsonFilter
{
    /**
     * @var SoftDeleteableListener|null
     */
    protected $listener;

    /**
     * @var DocumentManager|null
     *
     * @deprecated `BsonFilter::$dm` is a protected property, thus this property is not required
     */
    protected $documentManager;

    /**
     * @var array<string, bool>
     */
    protected $disabled = [];

    /**
     * Gets the criteria part to add to a query.
     *
     * @return array The criteria array, if there is available, empty array otherwise
     */
    public function addFilterCriteria(ClassMetadata $targetEntity): array
    {
        $class = $targetEntity->getName();
        if (true === ($this->disabled[$class] ?? false)) {
            return [];
        }
        if (true === ($this->disabled[$targetEntity->rootDocumentName] ?? false)) {
            return [];
        }

        $config = $this->getListener()->getConfiguration($this->getDocumentManager(), $targetEntity->name);

        if (!isset($config['softDeleteable']) || !$config['softDeleteable']) {
            return [];
        }

        $column = $targetEntity->getFieldMapping($config['fieldName']);

        if (isset($config['timeAware']) && $config['timeAware']) {
            return [
                '$or' => [
                    [$column['fieldName'] => null],
                    [$column['fieldName'] => ['$gt' => new \DateTime('now')]],
                ],
            ];
        }

        return [
            $column['fieldName'] => null,
        ];
    }

    /**
     * @param string $class
     * @phpstan-param class-string $class
     *
     * @return void
     */
    public function disableForDocument($class)
    {
        $this->disabled[$class] = true;
    }

    /**
     * @param string $class
     * @phpstan-param class-string $class
     *
     * @return void
     */
    public function enableForDocument($class)
    {
        $this->disabled[$class] = false;
    }

    /**
     * @return SoftDeleteableListener|null
     */
    protected function getListener()
    {
        if (null === $this->listener) {
            $em = $this->getDocumentManager();
            $evm = $em->getEventManager();

            foreach ($evm->getListeners() as $listeners) {
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
     * @return DocumentManager
     */
    protected function getDocumentManager()
    {
        // Remove the following assignment on the next major release.
        $this->documentManager = $this->dm;

        return $this->dm;
    }
}
