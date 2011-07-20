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
    public function postSlugBuild(SluggableAdapter $ea, $field, $object, &$slug)
    {
        $this->originalTransliterator = $this->sluggable->getTransliterator();
        $this->sluggable->setTransliterator(array($this, 'transliterate'));
        $this->parts = array();
        $wrapped = AbstractWrapper::wrapp($object, $this->om);
        do {
            $relation = $wrapped->getPropertyValue($this->options['relation']);
            if ($relation) {
                $wrappedRelation = AbstractWrapper::wrapp($relation, $this->om);
                array_unshift($this->parts, $wrappedRelation->getPropertyValue($this->options['targetField']));
                $wrapped = $wrappedRelation;
            }
        } while ($this->options['recursive'] && $relation);
        //var_dump($slug);
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

    public function transliterate($text, $separator, $object)
    {
        foreach ($this->parts as &$part) {
            $part = call_user_func_array(
                $this->originalTransliterator,
                array($part, $separator, $object)
            );
        }
        $this->parts[] = call_user_func_array(
            $this->originalTransliterator,
            array($text, $separator, $object)
        );
        $this->sluggable->setTransliterator($this->originalTransliterator);
        return implode($this->options['separator'], $this->parts);
    }
}