<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Translatable\Document\Repository;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Iterator\Iterator;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\ODM\MongoDB\Types\Type;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Gedmo\Tool\Wrapper\MongoDocumentWrapper;
use Gedmo\Translatable\Document\MappedSuperclass\AbstractPersonalTranslation;
use Gedmo\Translatable\Mapping\Event\Adapter\ODM as TranslatableAdapterODM;
use Gedmo\Translatable\TranslatableListener;

/**
 * The TranslationRepository has some useful functions
 * to interact with translations.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
class TranslationRepository extends DocumentRepository
{
    /**
     * Current TranslatableListener instance used
     * in EntityManager
     *
     * @var TranslatableListener|null
     */
    private $listener;

    public function __construct(DocumentManager $dm, UnitOfWork $uow, ClassMetadata $class)
    {
        if ($class->getReflectionClass()->isSubclassOf(AbstractPersonalTranslation::class)) {
            throw new \Gedmo\Exception\UnexpectedValueException('This repository is useless for personal translations');
        }
        parent::__construct($dm, $uow, $class);
    }

    /**
     * Makes additional translation of $document $field into $locale
     * using $value
     *
     * @param object $document
     * @param string $field
     * @param string $locale
     * @param mixed  $value
     *
     * @return static
     */
    public function translate($document, $field, $locale, $value)
    {
        $meta = $this->dm->getClassMetadata(get_class($document));
        $listener = $this->getTranslatableListener();
        $config = $listener->getConfiguration($this->dm, $meta->getName());
        if (!isset($config['fields']) || !in_array($field, $config['fields'], true)) {
            throw new \Gedmo\Exception\InvalidArgumentException("Document: {$meta->getName()} does not translate field - {$field}");
        }
        $modRecordValue = (!$listener->getPersistDefaultLocaleTranslation() && $locale === $listener->getDefaultLocale())
            || $listener->getTranslatableLocale($document, $meta, $this->getDocumentManager()) === $locale
        ;
        if ($modRecordValue) {
            $meta->getReflectionProperty($field)->setValue($document, $value);
            $this->dm->persist($document);
        } else {
            if (isset($config['translationClass'])) {
                $class = $config['translationClass'];
            } else {
                $ea = new TranslatableAdapterODM();
                $class = $listener->getTranslationClass($ea, $config['useObjectClass']);
            }
            $foreignKey = $meta->getReflectionProperty($meta->getIdentifier()[0])->getValue($document);
            $objectClass = $config['useObjectClass'];
            $transMeta = $this->dm->getClassMetadata($class);
            $trans = $this->findOneBy(compact('locale', 'field', 'objectClass', 'foreignKey'));
            if (!$trans) {
                $trans = $transMeta->newInstance();
                $transMeta->getReflectionProperty('foreignKey')->setValue($trans, $foreignKey);
                $transMeta->getReflectionProperty('objectClass')->setValue($trans, $objectClass);
                $transMeta->getReflectionProperty('field')->setValue($trans, $field);
                $transMeta->getReflectionProperty('locale')->setValue($trans, $locale);
            }
            $mapping = $meta->getFieldMapping($field);
            $type = $this->getType($mapping['type']);
            $transformed = $type->convertToDatabaseValue($value);
            $transMeta->getReflectionProperty('content')->setValue($trans, $transformed);
            if ($this->dm->getUnitOfWork()->isInIdentityMap($document)) {
                $this->dm->persist($trans);
            } else {
                $oid = spl_object_id($document);
                $listener->addPendingTranslationInsert($oid, $trans);
            }
        }

        return $this;
    }

    /**
     * Loads all translations with all translatable
     * fields from the given entity
     *
     * @param object $document
     *
     * @return array list of translations in locale groups
     */
    public function findTranslations($document)
    {
        $result = [];
        $wrapped = new MongoDocumentWrapper($document, $this->dm);
        if ($wrapped->hasValidIdentifier()) {
            $documentId = $wrapped->getIdentifier();

            $translationMeta = $this->getClassMetadata(); // table inheritance support

            $config = $this
                ->getTranslatableListener()
                ->getConfiguration($this->dm, $wrapped->getMetadata()->getName());

            if (!$config) {
                return $result;
            }

            $documentClass = $config['useObjectClass'];

            $translationClass = $config['translationClass'] ?? $translationMeta->rootDocumentName;

            $qb = $this->dm->createQueryBuilder($translationClass);
            $q = $qb->field('foreignKey')->equals($documentId)
                ->field('objectClass')->equals($documentClass)
                ->field('content')->exists(true)->notEqual(null)
                ->sort('locale', 'asc')
                ->getQuery();

            $q->setHydrate(false);
            $data = $q->execute();
            if ($data instanceof Iterator) {
                $data = $data->toArray();
            }
            if ($data && is_array($data) && count($data)) {
                foreach ($data as $row) {
                    $result[$row['locale']][$row['field']] = $row['content'];
                }
            }
        }

        return $result;
    }

    /**
     * Find the object $class by the translated field.
     * Result is the first occurrence of translated field.
     * Query can be slow, since there are no indexes on such
     * columns
     *
     * @param string $field
     * @param string $value
     * @param string $class
     *
     * @return object|null instance of $class or null if not found
     */
    public function findObjectByTranslatedField($field, $value, $class)
    {
        $meta = $this->dm->getClassMetadata($class);

        if (!$meta->hasField($field)) {
            return null;
        }

        $qb = $this->createQueryBuilder();
        $q = $qb->field('field')->equals($field)
            ->field('objectClass')->equals($meta->rootDocumentName)
            ->field('content')->equals($value)
            ->getQuery();

        $q->setHydrate(false);
        $result = $q->execute();

        if ($result instanceof Iterator) {
            $result = $result->toArray();
        }

        $id = $result[0]['foreign_key'] ?? null;

        if (null === $id) {
            return null;
        }

        return $this->dm->find($class, $id);
    }

    /**
     * Loads all translations with all translatable
     * fields by a given document primary key
     *
     * @param mixed $id primary key value of document
     *
     * @return array
     */
    public function findTranslationsByObjectId($id)
    {
        $result = [];
        if ($id) {
            $qb = $this->createQueryBuilder();
            $q = $qb->field('foreignKey')->equals($id)
                ->field('content')->exists(true)->notEqual(null)
                ->sort('locale', 'asc')
                ->getQuery();

            $q->setHydrate(false);
            $data = $q->execute();

            if ($data instanceof Iterator) {
                $data = $data->toArray();
            }
            if ($data && is_array($data) && count($data)) {
                foreach ($data as $row) {
                    $result[$row['locale']][$row['field']] = $row['content'];
                }
            }
        }

        return $result;
    }

    /**
     * Get the currently used TranslatableListener
     *
     * @throws \Gedmo\Exception\RuntimeException if listener is not found
     */
    private function getTranslatableListener(): TranslatableListener
    {
        if (null === $this->listener) {
            foreach ($this->dm->getEventManager()->getListeners() as $event => $listeners) {
                foreach ($listeners as $hash => $listener) {
                    if ($listener instanceof TranslatableListener) {
                        return $this->listener = $listener;
                    }
                }
            }

            throw new \Gedmo\Exception\RuntimeException('The translation listener could not be found');
        }

        return $this->listener;
    }

    private function getType(string $type): Type
    {
        return Type::getType($type);
    }
}
