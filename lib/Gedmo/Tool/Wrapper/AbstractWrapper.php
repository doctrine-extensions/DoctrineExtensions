<?php

namespace Gedmo\Tool\Wrapper;

use Doctrine\ORM\EntityManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\Common\Persistence\ObjectManager;
use Gedmo\Tool\WrapperInterface;
use Gedmo\Exception\UnsupportedObjectManager;

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
     * @var \Doctrine\Common\Persistence\ObjectManager
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
     * @param \Doctrine\Common\Persistence\ObjectManager $om
     * @throws \Gedmo\Exception\UnsupportedObjectManager
     * @return \Gedmo\Tool\WrapperInterface
     */
    public static function wrap($object, ObjectManager $om)
    {
        $oid = spl_object_hash($object);
        if (!isset(self::$wrappedObjectReferences[$oid])) {
            if ($om instanceof EntityManager) {
                self::$wrappedObjectReferences[$oid] = new EntityWrapper($object, $om);
            } elseif ($om instanceof DocumentManager) {
                self::$wrappedObjectReferences[$oid] = new MongoDocumentWrapper($object, $om);
            } else {
                throw new UnsupportedObjectManager('Given object manager is not managed by wrapper');
            }
        }
        return self::$wrappedObjectReferences[$oid];
    }

    public static function clear()
    {
        self::$wrappedObjectReferences = array();
    }

    /**
     * {@inheritDoc}
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * {@inheritDoc}
     */
    public function getMetadata()
    {
        return $this->meta;
    }

    /**
     * {@inheritDoc}
     */
    public function populate(array $data)
    {
        foreach ($data as $field => $value) {
            $this->setPropertyValue($field, $value);
        }
        return $this;
    }
}
