<?php

namespace Gedmo\Tool\Wrapper;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Gedmo\Exception\UnsupportedObjectManagerException;
use Gedmo\Tool\WrapperInterface;

/**
 * Wraps entity or proxy for more convenient
 * manipulation
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
abstract class AbstractWrapper implements WrapperInterface
{
    /**
     * Object metadata
     *
     * @var object
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
     * @var \Doctrine\Persistence\ObjectManager
     */
    protected $om;

    /**
     * List of wrapped object references
     *
     * @var array
     */
    private static $wrappedObjectReferences;

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
        } elseif ($om instanceof DocumentManager) {
            return new MongoDocumentWrapper($object, $om);
        }
        throw new UnsupportedObjectManagerException('Given object manager is not managed by wrapper');
    }

    public static function clear()
    {
        self::$wrappedObjectReferences = [];
    }

    /**
     * {@inheritdoc}
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata()
    {
        return $this->meta;
    }

    /**
     * {@inheritdoc}
     */
    public function populate(array $data)
    {
        foreach ($data as $field => $value) {
            $this->setPropertyValue($field, $value);
        }

        return $this;
    }
}
