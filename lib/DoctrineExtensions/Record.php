<?php

namespace DoctrineExtensions;

use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * Base class for Doctrine Entity used for individual DRY abstraction
 * Note that for entity manager retrieval use diferent behavior
 * compared to your framework. Or add a setter. getId() method may also
 * be diferent, or use reflection
 */
class Record
{
    /**
     * Populates the object with given parameters
     * 
     * @param array $params
     * @return void
     */
    public function populate(array $params)
    {
        $em = $this->getEntityManager();
        $class = $em->getClassMetadata(get_called_class());
        
        foreach ($params as $fieldName => $value) {
            if (strtotime($value) !== false) {
                $value = new DateTime($value);
            }
            if (array_key_exists($fieldName, $class->associationMappings)) {
                $targetEntity = $class->associationMappings[$fieldName]['targetEntity'];
                $value = $em->getRepository($targetEntity)->find($value);
            }
            $setter = 'set' . ucfirst($fieldName);
            if (method_exists($this, $setter)) {
                $this->{$setter}($value);
            } else {
                $this->{$fieldName} = $value;
            }
        }
    }
    
    /**
     * Get the list of single associated entity classes
     * 
     * @return array
     */
    public function getAssociatedEntityClasses()
    {
        $em = $this->getEntityManager();
        $class = $em->getClassMetadata(get_called_class());
        $entityClasses = array();
        foreach ($class->associationMappings as $fieldName => $mapping) {
            if ($class->isSingleValuedAssociation($fieldName)) {
                $entityClasses[$fieldName] = $mapping['targetEntity'];
            }
        }
        return $entityClasses;
    }
    
    /**
     * Get the list of associated entities by
     * single(TO-ONE) relation. 
     * 
     * @return array
     */
    public function getAssociatedEntities()
    {
        $em = $this->getEntityManager();
        $entities = array();
        foreach ($this->getAssociatedEntityClasses() as $fieldName => $class) {
            $entities[$fieldName] = $em->getRepository($class)->findAll();
        }
        return $entities;
    }
    
    /**
     * Converts all metadata properties to an array
     * 
     * @return array
     */
    public function toArray()
    {
        $em = $this->getEntityManager();
        $class = $em->getClassMetadata(get_called_class());
        $data = array();

        foreach ($class->fieldMappings as $fieldName => $mapping) {
            $getter = 'get' . ucfirst($fieldName);
            if (method_exists($this, $getter)) {
                 $object = $this->{$getter}();
            } else {
                $reflectionProperty = $class->reflFields[$fieldName];
                $object = $reflectionProperty->getValue($this);
            }
            if (is_object($object) && $object instanceof \DateTime) {
                $object = $object->format('Y-m-d H:i:s');
            }
            $data[$fieldName] = $object;
        }
        foreach ($class->associationMappings as $fieldName => $mapping) {
            if ($class->isSingleValuedAssociation($fieldName)) {
                $reflectionProperty = $class->reflFields[$fieldName];
                $relation = $reflectionProperty->getValue($this);
                if ($relation) {
                	$data[$fieldName] = $relation->getId();
                }
            }
        }
        
        return $data;
    }
    
    /**
     * Get entity manager
     * 
     * @internal used for Zend Framework
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getEntityManager()
    {
    	return \Zend_Registry::get('em');
    }
}