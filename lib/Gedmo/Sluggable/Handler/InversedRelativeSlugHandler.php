<?php

namespace Gedmo\Sluggable\Handler;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\Sluggable\Mapping\Event\SluggableAdapter;
use Gedmo\Tool\Wrapper\AbstractWrapper;
use Gedmo\Exception\InvalidMappingException;

/**
* Sluggable handler which should be used for inversed relation mapping
* used together with RelativeSlugHandler. Updates back related slug on
* relation changes
*
* @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
* @package Gedmo.Sluggable.Handler
* @subpackage InversedRelativeSlugHandler
* @link http://www.gediminasm.org
* @license MIT License (http://www.opensource.org/licenses/mit-license.php)
*/
class InversedRelativeSlugHandler implements SlugHandlerInterface
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
     * $options = array(
     *     'relationClass' => 'objectclass',
     *     'inverseSlugField' => 'slug',
     *     'mappedBy' => 'relationField'
     * )
     * {@inheritDoc}
     */
    public function __construct(SluggableListener $sluggable)
    {
        $this->sluggable = $sluggable;
    }

    /**
    * {@inheritDoc}
    */
    public function getOptions($object)
    {
        $meta = $this->om->getClassMetadata(get_class($object));
        if (!isset($this->options[$meta->name])) {
            $config = $this->sluggable->getConfiguration($this->om, $meta->name);
            $this->options[$meta->name] = $config['handlers'][get_called_class()];
        }
        return $this->options[$meta->name];
    }

    /**
     * {@inheritDoc}
     */
    public function onChangeDecision(SluggableAdapter $ea, $slugFieldConfig, $object, &$slug, &$needToChangeSlug)
    {}

    /**
     * {@inheritDoc}
     */
    public function postSlugBuild(SluggableAdapter $ea, array &$config, $object, &$slug)
    {}

    /**
     * {@inheritDoc}
     */
    public static function validate(array $options, ClassMetadata $meta)
    {}

    /**
     * {@inheritDoc}
     */
    public function onSlugCompletion(SluggableAdapter $ea, array &$config, $object, &$slug)
    {
        $this->om = $ea->getObjectManager();
        $isInsert = $this->om->getUnitOfWork()->isScheduledForInsert($object);
        if (!$isInsert) {
            $options = $this->getOptions($object);
            $wrapped = AbstractWrapper::wrapp($object, $this->om);
            $oldSlug = $wrapped->getPropertyValue($config['slug']);
            $mappedByConfig = $this->sluggable->getConfiguration(
                $this->om,
                $options['relationClass']
            );
            if ($mappedByConfig) {
                $meta = $this->om->getClassMetadata($options['relationClass']);
                if (!$meta->isSingleValuedAssociation($options['mappedBy'])) {
                    throw new InvalidMappingException("Unable to find ".$wrapped->getMetadata()->name." relation - [{$options['mappedBy']}] in class - {$meta->name}");
                }
                if (!isset($mappedByConfig['slugFields'][$options['inverseSlugField']])) {
                    throw new InvalidMappingException("Unable to find slug field - [{$options['inverseSlugField']}] in class - {$meta->name}");
                }
                $mappedByConfig['slug'] = $mappedByConfig['slugFields'][$options['inverseSlugField']]['slug'];
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
                        if (property_exists($object, '__isInitialized__') && !$object->__isInitialized__) {
                            continue;
                        }
                        $oid = spl_object_hash($object);
                        $objectSlug = $meta->getReflectionProperty($mappedByConfig['slug'])->getValue($object);
                        if (preg_match("@^{$oldSlug}@smi", $objectSlug)) {
                            $objectSlug = str_replace($oldSlug, $slug, $objectSlug);
                            $meta->getReflectionProperty($mappedByConfig['slug'])->setValue($object, $objectSlug);
                            $ea->setOriginalObjectProperty($uow, $oid, $mappedByConfig['slug'], $objectSlug);
                        }
                    }
                }
            }
        }
    }
}