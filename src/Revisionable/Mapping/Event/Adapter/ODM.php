<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Revisionable\Mapping\Event\Adapter;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata as MongoDBODMClassMetadata;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Gedmo\Mapping\Event\Adapter\ODM as BaseAdapterODM;
use Gedmo\Revisionable\Document\Revision;
use Gedmo\Revisionable\Mapping\Event\RevisionableAdapter;
use Gedmo\Revisionable\Revisionable;
use Gedmo\Revisionable\RevisionInterface;
use Gedmo\Tool\Wrapper\MongoDocumentWrapper;

/**
 * Doctrine event adapter for the Revisionable extension when using the Doctrine MongoDB ORM.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class ODM extends BaseAdapterODM implements RevisionableAdapter
{
    /**
     * Get the default object class name used to store revisions.
     *
     * @phpstan-return class-string<RevisionInterface<Revisionable|object>>
     */
    public function getDefaultRevisionClass(): string
    {
        return Revision::class;
    }

    /**
     * Checks whether an identifier should be generated post insert.
     */
    public function isPostInsertGenerator(ClassMetadata $meta): bool
    {
        return false;
    }

    /**
     * Get the new version number for an object.
     *
     * @phpstan-return positive-int
     */
    public function getNewVersion(ClassMetadata $meta, object $object): int
    {
        assert($meta instanceof MongoDBODMClassMetadata);

        $dm = $this->getObjectManager();

        $documentWrapper = new MongoDocumentWrapper($object, $dm);

        $documentMetadata = $documentWrapper->getMetadata();
        $documentId = (string) $documentWrapper->getIdentifier(false, true);
        $documentClass = $documentMetadata->getName();

        $qb = $dm->createQueryBuilder($meta->getName());
        $qb->select('version');
        $qb->field('revisionableId')->equals($documentId);
        $qb->field('revisionableClass')->equals($documentClass);
        $qb->sort('version', 'DESC');
        $qb->limit(1);

        $q = $qb->getQuery();
        $q->setHydrate(false);

        $result = $q->getSingleResult();

        if ($result) {
            $result = (int) $result['version'] + 1;
        }

        return (int) $result;
    }
}
