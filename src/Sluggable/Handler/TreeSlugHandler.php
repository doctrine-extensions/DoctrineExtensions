<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Sluggable\Handler;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\Proxy;
use Gedmo\Exception\InvalidMappingException;
use Gedmo\Sluggable\Mapping\Event\SluggableAdapter;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\Tool\Wrapper\AbstractWrapper;

use function Symfony\Component\String\u;

/**
 * Sluggable handler which slugs all parent nodes
 * recursively and synchronizes on updates. For instance
 * category tree slug could look like "food/fruits/apples"
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @final since gedmo/doctrine-extensions 3.11
 */
class TreeSlugHandler implements SlugHandlerWithUniqueCallbackInterface
{
    public const SEPARATOR = '/';

    /**
     * @var ObjectManager
     */
    protected $om;

    /**
     * @var SluggableListener
     */
    protected $sluggable;

    private string $prefix = '';

    private string $suffix = '';

    /**
     * True if node is being inserted
     */
    private bool $isInsert = false;

    /**
     * Transliterated parent slug
     */
    private string $parentSlug = '';

    /**
     * Used path separator
     */
    private string $usedPathSeparator = self::SEPARATOR;

    public function __construct(SluggableListener $sluggable)
    {
        $this->sluggable = $sluggable;
    }

    public function onChangeDecision(SluggableAdapter $ea, array &$config, $object, &$slug, &$needToChangeSlug)
    {
        $this->om = $ea->getObjectManager();
        $this->isInsert = $this->om->getUnitOfWork()->isScheduledForInsert($object);
        $options = $config['handlers'][static::class];

        $this->usedPathSeparator = $options['separator'] ?? self::SEPARATOR;
        $this->prefix = $options['prefix'] ?? '';
        $this->suffix = $options['suffix'] ?? '';

        if (!$this->isInsert && !$needToChangeSlug) {
            $changeSet = $ea->getObjectChangeSet($this->om->getUnitOfWork(), $object);
            if (isset($changeSet[$options['parentRelationField']])) {
                $needToChangeSlug = true;
            }
        }
    }

    public function postSlugBuild(SluggableAdapter $ea, array &$config, $object, &$slug)
    {
        $options = $config['handlers'][static::class];
        $this->parentSlug = '';

        $wrapped = AbstractWrapper::wrap($object, $this->om);
        if ($parent = $wrapped->getPropertyValue($options['parentRelationField'])) {
            $parent = AbstractWrapper::wrap($parent, $this->om);
            $this->parentSlug = $parent->getPropertyValue($config['slug']);

            // if needed, remove suffix from parentSlug, so we can use it to prepend it to our slug
            if (isset($options['suffix'])) {
                $this->parentSlug = u($this->parentSlug)->trimSuffix($options['suffix'])->toString();
            }
        }
    }

    /**
     * @param ClassMetadata<object> $meta
     */
    public static function validate(array $options, ClassMetadata $meta)
    {
        if (!$meta->isSingleValuedAssociation($options['parentRelationField'])) {
            throw new InvalidMappingException("Unable to find tree parent slug relation through field - [{$options['parentRelationField']}] in class - {$meta->getName()}");
        }
    }

    public function beforeMakingUnique(SluggableAdapter $ea, array &$config, $object, &$slug)
    {
        $slug = $this->transliterate($slug, $config['separator'], $object);
    }

    public function onSlugCompletion(SluggableAdapter $ea, array &$config, $object, &$slug)
    {
        if (!$this->isInsert) {
            $wrapped = AbstractWrapper::wrap($object, $this->om);
            $meta = $wrapped->getMetadata();
            $target = $wrapped->getPropertyValue($config['slug']);
            $config['pathSeparator'] = $this->usedPathSeparator;
            $ea->replaceRelative($object, $config, $target.$config['pathSeparator'], $slug);
            $uow = $this->om->getUnitOfWork();
            // update in memory objects
            foreach ($uow->getIdentityMap() as $className => $objects) {
                // for inheritance mapped classes, only root is always in the identity map
                if ($className !== $wrapped->getRootObjectName()) {
                    continue;
                }
                foreach ($objects as $object) {
                    // @todo: Remove the check against `method_exists()` in the next major release.
                    if (($object instanceof Proxy || method_exists($object, '__isInitialized')) && !$object->__isInitialized()) {
                        continue;
                    }

                    $objectSlug = (string) $meta->getFieldValue($object, $config['slug']);
                    if (preg_match("@^{$target}{$config['pathSeparator']}@smi", $objectSlug)) {
                        $objectSlug = str_replace($target, $slug, $objectSlug);
                        $meta->setFieldValue($object, $config['slug'], $objectSlug);
                        $ea->setOriginalObjectProperty($uow, $object, $config['slug'], $objectSlug);
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
     *
     * @return string
     */
    public function transliterate($text, $separator, $object)
    {
        $slug = $text.$this->suffix;

        if (strlen($this->parentSlug)) {
            $slug = $this->parentSlug.$this->usedPathSeparator.$slug;
        } else {
            // if no parentSlug, apply our prefix
            $slug = $this->prefix.$slug;
        }

        return $slug;
    }

    public function handlesUrlization()
    {
        return false;
    }
}
