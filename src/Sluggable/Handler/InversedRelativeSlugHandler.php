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

/**
 * Sluggable handler which should be used for inversed relation mapping
 * used together with RelativeSlugHandler. Updates back related slug on
 * relation changes
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @final since gedmo/doctrine-extensions 3.11
 */
class InversedRelativeSlugHandler implements SlugHandlerInterface
{
    /**
     * @var ObjectManager
     */
    protected $om;

    /**
     * @var SluggableListener
     */
    protected $sluggable;

    /**
     * $options = array(
     *     'relationClass' => 'objectclass',
     *     'inverseSlugField' => 'slug',
     *     'mappedBy' => 'relationField'
     * )
     * {@inheritdoc}
     */
    public function __construct(SluggableListener $sluggable)
    {
        $this->sluggable = $sluggable;
    }

    public function onChangeDecision(SluggableAdapter $ea, array &$config, $object, &$slug, &$needToChangeSlug)
    {
    }

    public function postSlugBuild(SluggableAdapter $ea, array &$config, $object, &$slug)
    {
    }

    /**
     * @param ClassMetadata<object> $meta
     */
    public static function validate(array $options, ClassMetadata $meta)
    {
        if (!isset($options['relationClass']) || !strlen($options['relationClass'])) {
            throw new InvalidMappingException("'relationClass' option must be specified for object slug mapping - {$meta->getName()}");
        }
        if (!isset($options['mappedBy']) || !strlen($options['mappedBy'])) {
            throw new InvalidMappingException("'mappedBy' option must be specified for object slug mapping - {$meta->getName()}");
        }
        if (!isset($options['inverseSlugField']) || !strlen($options['inverseSlugField'])) {
            throw new InvalidMappingException("'inverseSlugField' option must be specified for object slug mapping - {$meta->getName()}");
        }
    }

    public function onSlugCompletion(SluggableAdapter $ea, array &$config, $object, &$slug)
    {
        $this->om = $ea->getObjectManager();
        $isInsert = $this->om->getUnitOfWork()->isScheduledForInsert($object);
        if (!$isInsert) {
            $options = $config['handlers'][static::class];
            $wrapped = AbstractWrapper::wrap($object, $this->om);
            $oldSlug = $wrapped->getPropertyValue($config['slug']);
            $mappedByConfig = $this->sluggable->getConfiguration(
                $this->om,
                $options['relationClass']
            );
            if ($mappedByConfig) {
                assert(class_exists($options['relationClass']));

                $meta = $this->om->getClassMetadata($options['relationClass']);
                if (!$meta->isSingleValuedAssociation($options['mappedBy'])) {
                    throw new InvalidMappingException('Unable to find '.$wrapped->getMetadata()->getName()." relation - [{$options['mappedBy']}] in class - {$meta->getName()}");
                }
                if (!isset($mappedByConfig['slugs'][$options['inverseSlugField']])) {
                    throw new InvalidMappingException("Unable to find slug field - [{$options['inverseSlugField']}] in class - {$meta->getName()}");
                }
                $mappedByConfig['slug'] = $mappedByConfig['slugs'][$options['inverseSlugField']]['slug'];
                $mappedByConfig['mappedBy'] = $options['mappedBy'];
                $ea->replaceInverseRelative($object, $mappedByConfig, $slug, $oldSlug);
                $uow = $this->om->getUnitOfWork();
                // update in memory objects
                foreach ($uow->getIdentityMap() as $className => $objects) {
                    // for inheritance mapped classes, only root is always in the identity map
                    if ($className !== $mappedByConfig['useObjectClass']) {
                        continue;
                    }
                    foreach ($objects as $object) {
                        // @todo: Remove the check against `method_exists()` in the next major release.
                        if (($object instanceof Proxy || method_exists($object, '__isInitialized')) && !$object->__isInitialized()) {
                            continue;
                        }

                        $objectSlug = (string) $meta->getReflectionProperty($mappedByConfig['slug'])->getValue($object);
                        if (preg_match("@^{$oldSlug}@smi", $objectSlug)) {
                            $objectSlug = str_replace($oldSlug, $slug, $objectSlug);
                            $meta->getReflectionProperty($mappedByConfig['slug'])->setValue($object, $objectSlug);
                            $ea->setOriginalObjectProperty($uow, $object, $mappedByConfig['slug'], $objectSlug);
                        }
                    }
                }
            }
        }
    }

    public function handlesUrlization()
    {
        return false;
    }
}
