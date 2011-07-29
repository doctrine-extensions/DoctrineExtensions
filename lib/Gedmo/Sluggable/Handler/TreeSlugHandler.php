<?php

namespace Gedmo\Sluggable\Handler;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\Sluggable\Mapping\Event\SluggableAdapter;
use Gedmo\Tool\Wrapper\AbstractWrapper;
use Gedmo\Exception\InvalidMappingException;

/**
* Sluggable handler which slugs all parent nodes
* recursively and synchronizes on updates. For instance
* category tree slug could look like "food/fruits/apples"
*
* @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
* @package Gedmo.Sluggable.Handler
* @subpackage TreeSlugHandler
* @link http://www.gediminasm.org
* @license MIT License (http://www.opensource.org/licenses/mit-license.php)
*/
class TreeSlugHandler implements SlugHandlerInterface
{
    /**
     * @var Doctrine\Common\Persistence\ObjectManager
     */
    protected $om;

    /**
     * @var Gedmo\Sluggable\SluggableListener
     */
    protected $sluggable;

    /**
     * Options for relative slug handler object
     * classes
     *
     * @var array
     */
    private $options;

    /**
     * Callable of original transliterator
     * which is used by sluggable
     *
     * @var callable
     */
    private $originalTransliterator;

    /**
     * List of node slugs to transliterate
     *
     * @var array
     */
    private $parts = array();

    /**
     * True if node is being inserted
     *
     * @var boolean
     */
    private $isInsert = false;

    /**
     * Used separator for slugs
     *
     * @var string
     */
    private $usedSeparator;

    /**
     * {@inheritDoc}
     */
    public function __construct(SluggableListener $sluggable)
    {
        $this->sluggable = $sluggable;
    }

    /**
     * $options = array(
     *     'separator' => '/',
     *     'parentRelation' => 'parent',
     *     'targetField' => 'title'
     * )
     * {@inheritDoc}
     */
    public function getOptions($object)
    {
        $meta = $this->om->getClassMetadata(get_class($object));
        if (!isset($this->options[$meta->name])) {
            $config = $this->sluggable->getConfiguration($this->om, $meta->name);
            $options = $config['handlers'][get_called_class()];
            $default = array(
                'separator' => '/'
            );
            $this->options[$meta->name] = array_merge($default, $options);
        }
        return $this->options[$meta->name];
    }

    /**
     * {@inheritDoc}
     */
    public function postSlugBuild(SluggableAdapter $ea, array &$config, $object, &$slug)
    {
        $this->om = $ea->getObjectManager();
        $options = $this->getOptions($object);
        $this->originalTransliterator = $this->sluggable->getTransliterator();
        $this->sluggable->setTransliterator(array($this, 'transliterate'));
        $this->parts = array();
        $this->isInsert = $this->om->getUnitOfWork()->isScheduledForInsert($object);

        $wrapped = AbstractWrapper::wrapp($object, $this->om);
        if ($this->isInsert) {
            do {
                $relation = $wrapped->getPropertyValue($options['parentRelation']);
                if ($relation) {
                    $wrappedRelation = AbstractWrapper::wrapp($relation, $this->om);
                    array_unshift($this->parts, $wrappedRelation->getPropertyValue($options['targetField']));
                    $wrapped = $wrappedRelation;
                }
            } while ($relation);
        } else {
            $this->parts = explode($options['separator'], $wrapped->getPropertyValue($config['slug']));
        }
        $this->usedSeparator = $options['separator'];
    }

    /**
     * {@inheritDoc}
     */
    public static function validate(array $options, ClassMetadata $meta)
    {
        if (!$meta->isSingleValuedAssociation($options['parentRelation'])) {
            throw new InvalidMappingException("Unable to find tree parent slug relation through field - [{$options['parentRelation']}] in class - {$meta->name}");
        }
        /*if (!$meta->isSingleValuedAssociation($options['relation'])) {
            throw new InvalidMappingException("Unable to find slug relation through field - [{$options['relation']}] in class - {$meta->name}");
        }*/
    }

    /**
     * {@inheritDoc}
     */
    public function onSlugCompletion(SluggableAdapter $ea, array &$config, $object, &$slug)
    {
        if (!$this->isInsert) {
            $wrapped = AbstractWrapper::wrapp($object, $this->om);
            $meta = $wrapped->getMetadata();
            $extConfig = $this->sluggable->getConfiguration($this->om, $meta->name);
            $config['useObjectClass'] = $extConfig['useObjectClass'];
            $target = $wrapped->getPropertyValue($config['slug']);
            $config['pathSeparator'] = $this->usedSeparator;
            $ea->replaceRelative($object, $config, $target.$this->usedSeparator, $slug);
            $uow = $this->om->getUnitOfWork();
            // update in memory objects
            foreach ($uow->getIdentityMap() as $className => $objects) {
                // for inheritance mapped classes, only root is always in the identity map
                if ($className !== $wrapped->getRootObjectName()) {
                    continue;
                }
                foreach ($objects as $object) {
                    if (property_exists($object, '__isInitialized__') && !$object->__isInitialized__) {
                        continue;
                    }
                    $oid = spl_object_hash($object);
                    $objectSlug = $meta->getReflectionProperty($config['slug'])->getValue($object);
                    if (preg_match("@^{$target}{$this->usedSeparator}@smi", $objectSlug)) {
                        $objectSlug = str_replace($target, $slug, $objectSlug);
                        $meta->getReflectionProperty($config['slug'])->setValue($object, $objectSlug);
                        $ea->setOriginalObjectProperty($uow, $oid, $config['slug'], $objectSlug);
                    }
                }
            }
        }
    }

    /**
     * Transliterates the slug and prefixes the slug
     * by collection of parent slugs
     *
     * @param string $text
     * @param string $separator
     * @param object $object
     * @return string
     */
    public function transliterate($text, $separator, $object)
    {
        if ($this->isInsert) {
            foreach ($this->parts as &$part) {
                $part = call_user_func_array(
                    $this->originalTransliterator,
                    array($part, $separator, $object)
                );
            }
        } else {
            array_pop($this->parts);
        }
        $this->parts[] = call_user_func_array(
            $this->originalTransliterator,
            array($text, $separator, $object)
        );
        $this->sluggable->setTransliterator($this->originalTransliterator);
        return implode($this->usedSeparator, $this->parts);
    }
}