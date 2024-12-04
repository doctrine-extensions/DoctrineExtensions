<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tool\Wrapper;

use Doctrine\Deprecations\Deprecation;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Gedmo\Exception\UnsupportedObjectManagerException;
use Gedmo\Tool\WrapperInterface;

/**
 * Wraps entity or proxy for more convenient
 * manipulation
 *
 * @template TClassMetadata of ClassMetadata<TObject>
 * @template TObject        of object
 * @template TObjectManager of ObjectManager
 *
 * @template-implements WrapperInterface<TClassMetadata, TObject, TObjectManager>
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
abstract class AbstractWrapper implements WrapperInterface
{
    /**
     * Object metadata
     *
     * @var TClassMetadata
     */
    protected $meta;

    /**
     * Wrapped object
     *
     * @var TObject
     */
    protected $object;

    /**
     * Object manager instance
     *
     * @var TObjectManager
     */
    protected $om;

    /**
     * Wrap object factory method
     *
     * @param TObject        $object
     * @param TObjectManager $om
     *
     * @psalm-param object        $object
     * @psalm-param ObjectManager $om
     *
     * @throws UnsupportedObjectManagerException
     *
     * @return WrapperInterface<TClassMetadata, TObject, TObjectManager>
     */
    public static function wrap($object, ObjectManager $om)
    {
        if ($om instanceof EntityManagerInterface) {
            return new EntityWrapper($object, $om);
        }
        if ($om instanceof DocumentManager) {
            return new MongoDocumentWrapper($object, $om);
        }

        throw new UnsupportedObjectManagerException('Given object manager is not managed by wrapper');
    }

    /**
     * @return void
     */
    public static function clear()
    {
        Deprecation::trigger(
            'gedmo/doctrine-extensions',
            'https://github.com/doctrine-extensions/DoctrineExtensions/pull/2410',
            'Using "%s()" method is deprecated since gedmo/doctrine-extensions 3.5 and will be removed in version 4.0.',
            __METHOD__
        );
    }

    /**
     * @return TObject
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * @return TClassMetadata
     */
    public function getMetadata()
    {
        return $this->meta;
    }

    public function populate(array $data)
    {
        Deprecation::trigger(
            'gedmo/doctrine-extensions',
            'https://github.com/doctrine-extensions/DoctrineExtensions/pull/2410',
            'Using "%s()" method is deprecated since gedmo/doctrine-extensions 3.5 and will be removed in version 4.0.',
            __METHOD__
        );

        foreach ($data as $field => $value) {
            $this->setPropertyValue($field, $value);
        }

        return $this;
    }
}
