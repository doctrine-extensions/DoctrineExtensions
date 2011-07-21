<?php

namespace Gedmo\Sluggable\Handler;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\Sluggable\Mapping\Event\SluggableAdapter;
use Gedmo\Tool\Wrapper\AbstractWrapper;
use Gedmo\Exception\InvalidMappingException;

class RelativeSlugHandler implements SlugHandlerInterface
{
    /**
     * @var Doctrine\Common\Persistence\ObjectManager
     */
    private $om;

    /**
     * @var Gedmo\Sluggable\SluggableListener
     */
    private $sluggable;

    /**
     * Options for relative slug handler
     *
     * @var array
     */
    private $options;

    private $originalTransliterator;

    private $parts = array();

    private $isInsert = false;

    /**
     * {@inheritDoc}
     */
    public function __construct(ObjectManager $om, SluggableListener $sluggable, array $options)
    {
        $this->om = $om;
        $this->sluggable = $sluggable;
        $default = array(
            'recursive' => true,
            'separator' => '/'
        );
        $this->options = array_merge($default, $options);
    }

    /**
     * {@inheritDoc}
     */
    public function postSlugBuild(SluggableAdapter $ea, array &$config, $object, &$slug)
    {
        $this->originalTransliterator = $this->sluggable->getTransliterator();
        $this->sluggable->setTransliterator(array($this, 'transliterate'));
        $this->parts = array();
        $this->isInsert = $this->om->getUnitOfWork()->isScheduledForInsert($object);

        $wrapped = AbstractWrapper::wrapp($object, $this->om);
        if ($this->isInsert) {
            do {
                $relation = $wrapped->getPropertyValue($this->options['relation']);
                if ($relation) {
                    $wrappedRelation = AbstractWrapper::wrapp($relation, $this->om);
                    array_unshift($this->parts, $wrappedRelation->getPropertyValue($this->options['targetField']));
                    $wrapped = $wrappedRelation;
                }
            } while ($this->options['recursive'] && $relation);
        } else {
            $this->parts = explode($this->options['separator'], $wrapped->getPropertyValue($config['slug']));
        }
    }

    /**
     * {@inheritDoc}
     */
    public static function validate(array $options, ClassMetadata $meta)
    {
        if (!$meta->isSingleValuedAssociation($options['relation'])) {
            throw new InvalidMappingException("Unable to find slug relation through field - [{$options['relation']}] in class - {$meta->name}");
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
            $extConfig = $this->sluggable->getConfiguration($this->om, $wrapped->getMetadata()->name);
            $config['useObjectClass'] = $extConfig['useObjectClass'];
            $ea->replaceRelative(
                $object,
                $config,
                $wrapped->getPropertyValue($config['slug']).$this->options['separator'],
                $slug
            );
        }
    }

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
        return implode($this->options['separator'], $this->parts);
    }
}