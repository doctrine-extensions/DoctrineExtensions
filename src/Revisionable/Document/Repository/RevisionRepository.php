<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Revisionable\Document\Repository;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Gedmo\Exception\RuntimeException;
use Gedmo\Exception\UnexpectedValueException;
use Gedmo\Revisionable\Document\MappedSuperclass\AbstractRevision;
use Gedmo\Revisionable\Revisionable;
use Gedmo\Revisionable\RevisionableListener;
use Gedmo\Tool\Wrapper\MongoDocumentWrapper;

/**
 * The RevisionRepository has some useful functions to interact with revisions.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @phpstan-template T of Revisionable|object
 *
 * @phpstan-extends DocumentRepository<AbstractRevision<T>>
 */
class RevisionRepository extends DocumentRepository
{
    /**
     * The revisionable listener associated with the document manager for the {@see AbstractRevision} document.
     *
     * @var RevisionableListener|bool|null
     *
     * @phpstan-var RevisionableListener<T>|null|false
     */
    private $listener = false;

    /**
     * Loads all revisions for the given document
     *
     * @return AbstractRevision[]
     *
     * @phpstan-param T $document
     *
     * @phpstan-return list<AbstractRevision<T>>
     */
    public function getRevisions(object $document): array
    {
        $documentWrapper = new MongoDocumentWrapper($document, $this->getDocumentManager());

        $documentId = (string) $documentWrapper->getIdentifier(false, true);
        $documentClass = $documentWrapper->getMetadata()->getName();

        $qb = $this->createQueryBuilder();
        $qb->field('revisionableId')->equals($documentId);
        $qb->field('revisionableClass')->equals($documentClass);
        $qb->sort('version', 'DESC');

        return $qb->getQuery()->getIterator()->toArray();
    }

    /**
     * Reverts the given document to the requested version, restoring all versioned fields to the state of that revision.
     *
     * Callers to this method will need to persist and flush changes to the document.
     *
     * @phpstan-param T $document
     * @phpstan-param positive-int $version
     *
     * @throws UnexpectedValueException
     */
    public function revert(object $document, int $version = 1): void
    {
        $documentWrapper = new MongoDocumentWrapper($document, $this->getDocumentManager());

        $documentMetadata = $documentWrapper->getMetadata();
        $documentId = (string) $documentWrapper->getIdentifier(false, true);
        $documentClass = $documentMetadata->getName();

        $qb = $this->createQueryBuilder();
        $qb->field('revisionableId')->equals($documentId);
        $qb->field('revisionableClass')->equals($documentClass);
        $qb->field('version')->lte($version);
        $qb->sort('version', 'ASC');

        $revisions = $qb->getQuery()->getIterator()->toArray();

        if ([] === $revisions) {
            throw new UnexpectedValueException(sprintf('Could not find any revisions for version %d of document %s.', $version, $documentClass));
        }

        $data = [[]];

        while ($revision = array_shift($revisions)) {
            $data[] = $revision->getData();
        }

        $data = array_merge(...$data);

        $this->fillDocument($document, $data);
    }

    /**
     * Fills a document's versioned fields with the given data
     *
     * @param array<string, mixed> $data
     *
     * @phpstan-param T $document
     */
    protected function fillDocument(object $document, array $data): void
    {
        $documentWrapper = new MongoDocumentWrapper($document, $this->getDocumentManager());

        $documentMeta = $documentWrapper->getMetadata();

        assert($documentMeta instanceof ClassMetadata);

        $config = $this->getListener()->getConfiguration($this->getDocumentManager(), $documentMeta->getName());
        $fields = $config['versioned'];

        foreach ($data as $field => $value) {
            if (!in_array($field, $fields, true)) {
                continue;
            }

            $mapping = $documentMeta->getFieldMapping($field);

            // Fill the embedded document
            if ($documentWrapper->isEmbeddedAssociation($field)) {
                if (!empty($value)) {
                    assert(class_exists($mapping['targetDocument']));

                    $embeddedMetadata = $this->getDocumentManager()->getClassMetadata($mapping['targetDocument']);
                    $document = $embeddedMetadata->newInstance();
                    $this->fillDocument($document, $value);
                    $value = $document;
                }
            } elseif ($documentMeta->isSingleValuedAssociation($field)) {
                assert(class_exists($mapping['targetDocument']));

                $value = $value ? $this->getDocumentManager()->getReference($mapping['targetDocument'], $value) : null;
            } else {
                $value = $value ? $documentWrapper->convertToPHPValue($value, $documentMeta->getTypeOfField($field)) : null;
            }

            $documentWrapper->setPropertyValue($field, $value);
            unset($fields[$field]);
        }

        /*
        if (count($fields)) {
            throw new UnexpectedValueException(sprintf('Could not fully revert document %s to version %d.', $documentMetadata->getName(), $version));
        }
        */
    }

    /**
     * Get the revisionable listener associated with the document manager for the {@see AbstractRevision} document.
     *
     * @throws RuntimeException if the listener is not found
     *
     * @phpstan-return RevisionableListener<T>
     */
    private function getListener(): RevisionableListener
    {
        if ($this->listener instanceof RevisionableListener) {
            return $this->listener;
        }

        if (false === $this->listener) {
            foreach ($this->getDocumentManager()->getEventManager()->getAllListeners() as $listeners) {
                foreach ($listeners as $listener) {
                    if ($listener instanceof RevisionableListener) {
                        return $this->listener = $listener;
                    }
                }
            }

            $this->listener = null;
        }

        throw new RuntimeException('The revisionable listener was not registered to the document manager.');
    }
}
