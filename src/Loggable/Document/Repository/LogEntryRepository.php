<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Loggable\Document\Repository;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Gedmo\Exception\RuntimeException;
use Gedmo\Exception\UnexpectedValueException;
use Gedmo\Loggable\Document\LogEntry;
use Gedmo\Loggable\Loggable;
use Gedmo\Loggable\LoggableListener;
use Gedmo\Tool\Wrapper\MongoDocumentWrapper;

/**
 * The LogEntryRepository has some useful functions
 * to interact with log entries.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @phpstan-template T of Loggable|object
 *
 * @phpstan-extends DocumentRepository<T>
 */
class LogEntryRepository extends DocumentRepository
{
    /**
     * Currently used loggable listener
     *
     * @var LoggableListener<T>|null
     */
    private ?LoggableListener $listener = null;

    /**
     * Loads all log entries for the
     * given $document
     *
     * @param object $document
     *
     * @return LogEntry[]
     *
     * @phpstan-param T $document
     *
     * @phpstan-return array<array-key, LogEntry<T>>
     */
    public function getLogEntries($document)
    {
        $wrapped = new MongoDocumentWrapper($document, $this->dm);
        $objectId = $wrapped->getIdentifier();

        $qb = $this->createQueryBuilder();
        $qb->field('objectId')->equals($objectId);
        $qb->field('objectClass')->equals($wrapped->getMetadata()->getName());
        $qb->sort('version', 'DESC');

        return $qb->getQuery()->getIterator()->toArray();
    }

    /**
     * Reverts given $document to $revision by
     * restoring all fields from that $revision.
     * After this operation you will need to
     * persist and flush the $document.
     *
     * @param object $document
     * @param int    $version
     *
     * @throws UnexpectedValueException
     *
     * @return void
     *
     * @phpstan-param T $document
     */
    public function revert($document, $version = 1)
    {
        $wrapped = new MongoDocumentWrapper($document, $this->dm);
        $objectMeta = $wrapped->getMetadata();
        $objectId = $wrapped->getIdentifier();

        $qb = $this->createQueryBuilder();
        $qb->field('objectId')->equals($objectId);
        $qb->field('objectClass')->equals($objectMeta->getName());
        $qb->field('version')->lte((int) $version);
        $qb->sort('version', 'ASC');

        $logs = $qb->getQuery()->getIterator()->toArray();

        if ([] === $logs) {
            throw new UnexpectedValueException('Count not find any log entries under version: '.$version);
        }

        $data = [[]];
        while ($log = array_shift($logs)) {
            $data[] = $log->getData();
        }
        $data = array_merge(...$data);
        $this->fillDocument($document, $data);
    }

    /**
     * Fills a documents versioned fields with data
     *
     * @param object               $document
     * @param array<string, mixed> $data
     *
     * @return void
     *
     * @phpstan-param T $document
     */
    protected function fillDocument($document, array $data)
    {
        $wrapped = new MongoDocumentWrapper($document, $this->dm);
        $objectMeta = $wrapped->getMetadata();

        assert($objectMeta instanceof ClassMetadata);

        $config = $this->getLoggableListener()->getConfiguration($this->dm, $objectMeta->getName());
        $fields = $config['versioned'];
        foreach ($data as $field => $value) {
            if (!in_array($field, $fields, true)) {
                continue;
            }
            $mapping = $objectMeta->getFieldMapping($field);
            // Fill the embedded document
            if ($wrapped->isEmbeddedAssociation($field)) {
                if (!empty($value)) {
                    assert(class_exists($mapping['targetDocument']));

                    $embeddedMetadata = $this->dm->getClassMetadata($mapping['targetDocument']);
                    $document = $embeddedMetadata->newInstance();
                    $this->fillDocument($document, $value);
                    $value = $document;
                }
            } elseif ($objectMeta->isSingleValuedAssociation($field)) {
                assert(class_exists($mapping['targetDocument']));

                $value = $value ? $this->dm->getReference($mapping['targetDocument'], $value) : null;
            }
            $wrapped->setPropertyValue($field, $value);
            unset($fields[$field]);
        }

        /*
        if (count($fields)) {
            throw new \Gedmo\Exception\UnexpectedValueException('Cound not fully revert the document to version: '.$version);
        }
        */
    }

    /**
     * Get the currently used LoggableListener
     *
     * @throws RuntimeException if listener is not found
     *
     * @phpstan-return LoggableListener<T>
     */
    private function getLoggableListener(): LoggableListener
    {
        if (null === $this->listener) {
            foreach ($this->dm->getEventManager()->getAllListeners() as $listeners) {
                foreach ($listeners as $listener) {
                    if ($listener instanceof LoggableListener) {
                        $this->listener = $listener;

                        break 2;
                    }
                }
            }

            if (null === $this->listener) {
                throw new RuntimeException('The loggable listener could not be found');
            }
        }

        return $this->listener;
    }
}
