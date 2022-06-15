<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tool\Wrapper;

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
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
abstract class AbstractWrapper implements WrapperInterface
{
    /**
     * Object metadata
     *
     * @var ClassMetadata
     */
    protected $meta;

    /**
     * Wrapped object
     *
     * @var object
     */
    protected $object;

    /**
     * Object manager instance
     *
     * @var ObjectManager
     */
    protected $om;

    /**
     * Wrap object factory method
     *
     * @param object $object
     *
     * @throws \Gedmo\Exception\UnsupportedObjectManagerException
     *
     * @return \Gedmo\Tool\WrapperInterface
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
        @trigger_error(sprintf(
            'Using "%s()" method is deprecated since gedmo/doctrine-extensions 3.5 and will be removed in version 4.0.',
            __METHOD__
        ), E_USER_DEPRECATED);
    }

    public function getObject()
    {
        return $this->object;
    }

    public function getMetadata()
    {
        return $this->meta;
    }

    public function populate(array $data)
    {
        @trigger_error(sprintf(
            'Using "%s()" method is deprecated since gedmo/doctrine-extensions 3.5 and will be removed in version 4.0.',
            __METHOD__
        ), E_USER_DEPRECATED);

        foreach ($data as $field => $value) {
            $this->setPropertyValue($field, $value);
        }

        return $this;
    }
}
