<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Sluggable;

use Doctrine\Common\EventArgs;
use Doctrine\Persistence\Event\LoadClassMetadataEventArgs;
use Doctrine\Persistence\ObjectManager;
use Gedmo\Mapping\MappedEventSubscriber;
use Gedmo\Sluggable\Handler\SlugHandlerInterface;
use Gedmo\Sluggable\Handler\SlugHandlerWithUniqueCallbackInterface;
use Gedmo\Sluggable\Mapping\Event\SluggableAdapter;
use Gedmo\Sluggable\Util\Urlizer;

/**
 * The SluggableListener handles the generation of slugs
 * for documents and entities.
 *
 * This behavior can impact the performance of your application
 * since it does some additional calculations on persisted objects.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author Klein Florian <florian.klein@free.fr>
 *
 * @phpstan-type SluggableConfiguration = array{
 *   mappedBy?: string,
 *   pathSeparator?: string,
 *   slug?: string,
 *   slugs?: array<string, array{
 *     fields?: string[],
 *     slug?: string,
 *     style?: string,
 *     dateFormat?: string,
 *     updatable?: bool,
 *     unique?: bool,
 *     unique_base?: string,
 *     separator?: string,
 *     prefix?: string,
 *     suffix?: string,
 *     handlers?: array<class-string, array{
 *       mappedBy?: string,
 *       inverseSlugField?: string,
 *       parentRelationField?: string,
 *       relationClass?: class-string,
 *       relationField?: string,
 *       relationSlugField?: string,
 *       separator?: string,
 *     }>,
 *   }>,
 *   unique?: bool,
 *   useObjectClass?: class-string,
 * }
 *
 * @phpstan-method SluggableConfiguration getConfiguration(ObjectManager $objectManager, $class)
 *
 * @method SluggableAdapter getEventAdapter(EventArgs $args)
 */
class SluggableListener extends MappedEventSubscriber
{
    /**
     * The power exponent to jump
     * the slug unique number by tens.
     *
     * @var int
     */
    private $exponent = 0;

    /**
     * Transliteration callback for slugs
     *
     * @var callable
     */
    private $transliterator = [Urlizer::class, 'transliterate'];

    /**
     * Urlize callback for slugs
     *
     * @var callable
     */
    private $urlizer = [Urlizer::class, 'urlize'];

    /**
     * List of inserted slugs for each object class.
     * This is needed in case there are identical slug
     * composition in number of persisted objects
     * during the same flush
     *
     * @var array
     */
    private $persisted = [];

    /**
     * List of initialized slug handlers
     *
     * @var array
     */
    private $handlers = [];

    /**
     * List of filters which are manipulated when slugs are generated
     *
     * @var array
     */
    private $managedFilters = [];

    /**
     * Specifies the list of events to listen
     *
     * @return string[]
     */
    public function getSubscribedEvents()
    {
        return [
            'onFlush',
            'loadClassMetadata',
            'prePersist',
        ];
    }

    /**
     * Set the transliteration callable method
     * to transliterate slugs
     *
     * @param callable $callable
     *
     * @throws \Gedmo\Exception\InvalidArgumentException
     *
     * @return void
     */
    public function setTransliterator($callable)
    {
        if (!is_callable($callable)) {
            throw new \Gedmo\Exception\InvalidArgumentException('Invalid transliterator callable parameter given');
        }
        $this->transliterator = $callable;
    }

    /**
     * Set the urlization callable method
     * to urlize slugs
     *
     * @param callable $callable
     *
     * @return void
     */
    public function setUrlizer($callable)
    {
        if (!is_callable($callable)) {
            throw new \Gedmo\Exception\InvalidArgumentException('Invalid urlizer callable parameter given');
        }
        $this->urlizer = $callable;
    }

    /**
     * Get currently used transliterator callable
     *
     * @return callable
     */
    public function getTransliterator()
    {
        return $this->transliterator;
    }

    /**
     * Get currently used urlizer callable
     *
     * @return callable
     */
    public function getUrlizer()
    {
        return $this->urlizer;
    }

    /**
     * Enables or disables the given filter when slugs are generated
     *
     * @param string $name
     * @param bool   $disable True by default
     *
     * @return void
     */
    public function addManagedFilter($name, $disable = true)
    {
        $this->managedFilters[$name] = ['disabled' => $disable];
    }

    /**
     * Removes a filter from the managed set
     *
     * @param string $name
     *
     * @return void
     */
    public function removeManagedFilter($name)
    {
        unset($this->managedFilters[$name]);
    }

    /**
     * Mapps additional metadata
     *
     * @param LoadClassMetadataEventArgs $eventArgs
     *
     * @return void
     */
    public function loadClassMetadata(EventArgs $eventArgs)
    {
        $this->loadMetadataForObjectClass($eventArgs->getObjectManager(), $eventArgs->getClassMetadata());
    }

    /**
     * Allows identifier fields to be slugged as usual
     *
     * @return void
     */
    public function prePersist(EventArgs $args)
    {
        $ea = $this->getEventAdapter($args);
        $om = $ea->getObjectManager();
        $object = $ea->getObject();
        $meta = $om->getClassMetadata(get_class($object));

        if ($config = $this->getConfiguration($om, $meta->getName())) {
            foreach ($config['slugs'] as $slugField => $options) {
                if ($meta->isIdentifier($slugField)) {
                    $meta->getReflectionProperty($slugField)->setValue($object, '__id__');
                }
            }
        }
    }

    /**
     * Generate slug on objects being updated during flush
     * if they require changing
     *
     * @return void
     */
    public function onFlush(EventArgs $args)
    {
        $this->persisted = [];
        $ea = $this->getEventAdapter($args);
        $om = $ea->getObjectManager();
        $uow = $om->getUnitOfWork();

        $this->manageFiltersBeforeGeneration($om);

        // process all objects being inserted, using scheduled insertions instead
        // of prePersist in case if record will be changed before flushing this will
        // ensure correct result. No additional overhead is encountered
        foreach ($ea->getScheduledObjectInsertions($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            if ($this->getConfiguration($om, $meta->getName())) {
                // generate first to exclude this object from similar persisted slugs result
                $this->generateSlug($ea, $object);
                $this->persisted[$ea->getRootObjectClass($meta)][] = $object;
            }
        }
        // we use onFlush and not preUpdate event to let other
        // event listeners be nested together
        foreach ($ea->getScheduledObjectUpdates($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            if ($this->getConfiguration($om, $meta->getName()) && !$uow->isScheduledForInsert($object)) {
                $this->generateSlug($ea, $object);
                $this->persisted[$ea->getRootObjectClass($meta)][] = $object;
            }
        }

        $this->manageFiltersAfterGeneration($om);
    }

    protected function getNamespace()
    {
        return __NAMESPACE__;
    }

    /**
     * Get the slug handler instance by $class name
     *
     * @phpstan-param class-string $class
     */
    private function getHandler(string $class): SlugHandlerInterface
    {
        if (!isset($this->handlers[$class])) {
            $this->handlers[$class] = new $class($this);
        }

        return $this->handlers[$class];
    }

    /**
     * Creates the slug for object being flushed
     */
    private function generateSlug(SluggableAdapter $ea, object $object): void
    {
        $om = $ea->getObjectManager();
        $meta = $om->getClassMetadata(get_class($object));
        $uow = $om->getUnitOfWork();
        $changeSet = $ea->getObjectChangeSet($uow, $object);
        $isInsert = $uow->isScheduledForInsert($object);
        $config = $this->getConfiguration($om, $meta->getName());

        foreach ($config['slugs'] as $slugField => $options) {
            $hasHandlers = [] !== $options['handlers'];
            $options['useObjectClass'] = $config['useObjectClass'];
            // collect the slug from fields
            $slug = $meta->getReflectionProperty($slugField)->getValue($object);

            // if slug should not be updated, skip it
            if (!$options['updatable'] && !$isInsert && (!isset($changeSet[$slugField]) || '__id__' === $slug)) {
                continue;
            }
            // must fetch the old slug from changeset, since $object holds the new version
            $oldSlug = isset($changeSet[$slugField]) ? $changeSet[$slugField][0] : $slug;
            $needToChangeSlug = false;

            // if slug is null, regenerate it, or needs an update
            if (null === $slug || '__id__' === $slug || !isset($changeSet[$slugField])) {
                $slug = '';

                foreach ($options['fields'] as $sluggableField) {
                    if (isset($changeSet[$sluggableField]) || isset($changeSet[$slugField])) {
                        $needToChangeSlug = true;
                    }
                    $value = $meta->getReflectionProperty($sluggableField)->getValue($object);
                    $slug .= $value instanceof \DateTimeInterface ? $value->format($options['dateFormat']) : $value;
                    $slug .= ' ';
                }
                // trim generated slug as it will have unnecessary trailing space
                $slug = trim($slug);
            } else {
                // slug was set manually
                $needToChangeSlug = true;
            }
            // notify slug handlers --> onChangeDecision
            if ($hasHandlers) {
                foreach ($options['handlers'] as $class => $handlerOptions) {
                    $this->getHandler($class)->onChangeDecision($ea, $options, $object, $slug, $needToChangeSlug);
                }
            }
            // if slug is changed, do further processing
            if ($needToChangeSlug) {
                $mapping = $meta->getFieldMapping($slugField);
                // notify slug handlers --> postSlugBuild
                $urlized = false;

                if ($hasHandlers) {
                    foreach ($options['handlers'] as $class => $handlerOptions) {
                        $this->getHandler($class)->postSlugBuild($ea, $options, $object, $slug);
                        if ($this->getHandler($class)->handlesUrlization()) {
                            $urlized = true;
                        }
                    }
                }

                // build the slug
                // Step 1: transliteration, changing 北京 to 'Bei Jing'
                $slug = call_user_func_array(
                    $this->transliterator,
                    [$slug, $options['separator'], $object]
                );

                // Step 2: urlization (replace spaces by '-' etc...)
                if (!$urlized) {
                    $slug = call_user_func_array(
                        $this->urlizer,
                        [$slug, $options['separator'], $object]
                    );
                }

                // add suffix/prefix
                $slug = $options['prefix'].$slug.$options['suffix'];

                // Step 3: stylize the slug
                switch ($options['style']) {
                    case 'camel':
                        $quotedSeparator = preg_quote($options['separator']);
                        $slug = preg_replace_callback('/^[a-z]|'.$quotedSeparator.'[a-z]/smi', static function ($m) {
                            return strtoupper($m[0]);
                        }, $slug);

                        break;

                    case 'lower':
                        if (function_exists('mb_strtolower')) {
                            $slug = mb_strtolower($slug);
                        } else {
                            $slug = strtolower($slug);
                        }

                        break;

                    case 'upper':
                        if (function_exists('mb_strtoupper')) {
                            $slug = mb_strtoupper($slug);
                        } else {
                            $slug = strtoupper($slug);
                        }

                        break;

                    default:
                        // leave it as is
                        break;
                }

                // cut slug if exceeded in length
                if (isset($mapping['length']) && strlen($slug) > $mapping['length']) {
                    $slug = substr($slug, 0, $mapping['length']);
                }

                if (isset($mapping['nullable']) && $mapping['nullable'] && 0 === strlen($slug)) {
                    $slug = null;
                }

                // notify slug handlers --> beforeMakingUnique
                if ($hasHandlers) {
                    foreach ($options['handlers'] as $class => $handlerOptions) {
                        $handler = $this->getHandler($class);
                        if ($handler instanceof SlugHandlerWithUniqueCallbackInterface) {
                            $handler->beforeMakingUnique($ea, $options, $object, $slug);
                        }
                    }
                }

                // make unique slug if requested
                if ($options['unique'] && null !== $slug) {
                    $this->exponent = 0;
                    $slug = $this->makeUniqueSlug($ea, $object, $slug, false, $options);
                }

                // notify slug handlers --> onSlugCompletion
                if ($hasHandlers) {
                    foreach ($options['handlers'] as $class => $handlerOptions) {
                        $this->getHandler($class)->onSlugCompletion($ea, $options, $object, $slug);
                    }
                }

                // set the final slug
                $meta->getReflectionProperty($slugField)->setValue($object, $slug);
                // recompute changeset
                $ea->recomputeSingleObjectChangeSet($uow, $meta, $object);
                // overwrite changeset (to set old value)
                $uow->propertyChanged($object, $slugField, $oldSlug, $slug);
            }
        }
    }

    /**
     * Generates the unique slug
     */
    private function makeUniqueSlug(SluggableAdapter $ea, object $object, string $preferredSlug, bool $recursing = false, array $config = []): string
    {
        $om = $ea->getObjectManager();
        $meta = $om->getClassMetadata(get_class($object));
        $similarPersisted = [];
        // extract unique base
        $base = false;

        if ($config['unique'] && isset($config['unique_base'])) {
            $base = $meta->getReflectionProperty($config['unique_base'])->getValue($object);
        }

        // collect similar persisted slugs during this flush
        if (isset($this->persisted[$class = $ea->getRootObjectClass($meta)])) {
            foreach ($this->persisted[$class] as $obj) {
                if (false !== $base && $meta->getReflectionProperty($config['unique_base'])->getValue($obj) !== $base) {
                    continue; // if unique_base field is not the same, do not take slug as similar
                }
                $slug = $meta->getReflectionProperty($config['slug'])->getValue($obj);
                $quotedPreferredSlug = preg_quote($preferredSlug);
                if (preg_match("@^{$quotedPreferredSlug}.*@smi", $slug)) {
                    $similarPersisted[] = [$config['slug'] => $slug];
                }
            }
        }

        // load similar slugs
        $result = array_merge($ea->getSimilarSlugs($object, $meta, $config, $preferredSlug), $similarPersisted);

        // leave only right slugs

        if (!$recursing) {
            // filter similar slugs
            $quotedSeparator = preg_quote($config['separator']);
            $quotedPreferredSlug = preg_quote($preferredSlug);
            foreach ($result as $key => $similar) {
                if (!preg_match("@{$quotedPreferredSlug}($|{$quotedSeparator}[\d]+$)@smi", $similar[$config['slug']])) {
                    unset($result[$key]);
                }
            }
        }

        if ($result) {
            $generatedSlug = $preferredSlug;
            $sameSlugs = [];

            foreach ((array) $result as $list) {
                $sameSlugs[] = $list[$config['slug']];
            }

            $i = pow(10, $this->exponent);
            $uniqueSuffix = (string) $i;
            if ($recursing || in_array($generatedSlug, $sameSlugs, true)) {
                do {
                    $generatedSlug = $preferredSlug.$config['separator'].$uniqueSuffix;
                    $uniqueSuffix = (string) ++$i;
                } while (in_array($generatedSlug, $sameSlugs, true));
            }

            $mapping = $meta->getFieldMapping($config['slug']);
            if (isset($mapping['length']) && strlen($generatedSlug) > $mapping['length']) {
                $generatedSlug = substr(
                    $generatedSlug,
                    0,
                    $mapping['length'] - (strlen($uniqueSuffix) + strlen($config['separator']))
                );
                $this->exponent = strlen($uniqueSuffix) - 1;
                if (substr($generatedSlug, -strlen($config['separator'])) == $config['separator']) {
                    $generatedSlug = substr($generatedSlug, 0, strlen($generatedSlug) - strlen($config['separator']));
                }
                $generatedSlug = $this->makeUniqueSlug($ea, $object, $generatedSlug, true, $config);
            }
            $preferredSlug = $generatedSlug;
        }

        return $preferredSlug;
    }

    private function manageFiltersBeforeGeneration(ObjectManager $om): void
    {
        $collection = $this->getFilterCollectionFromObjectManager($om);

        $enabledFilters = array_keys($collection->getEnabledFilters());

        // set each managed filter to desired status
        foreach ($this->managedFilters as $name => &$config) {
            $enabled = in_array($name, $enabledFilters, true);
            $config['previouslyEnabled'] = $enabled;

            if ($config['disabled']) {
                if ($enabled) {
                    $collection->disable($name);
                }
            } else {
                $collection->enable($name);
            }
        }
    }

    private function manageFiltersAfterGeneration(ObjectManager $om): void
    {
        $collection = $this->getFilterCollectionFromObjectManager($om);

        // Restore managed filters to their original status
        foreach ($this->managedFilters as $name => &$config) {
            if (true === $config['previouslyEnabled']) {
                $collection->enable($name);
            }

            unset($config['previouslyEnabled']);
        }
    }

    /**
     * Retrieves a FilterCollection instance from the given ObjectManager.
     *
     * @throws \Gedmo\Exception\InvalidArgumentException
     *
     * @return mixed
     */
    private function getFilterCollectionFromObjectManager(ObjectManager $om)
    {
        if (is_callable([$om, 'getFilters'])) {
            return $om->getFilters();
        }
        if (is_callable([$om, 'getFilterCollection'])) {
            return $om->getFilterCollection();
        }

        throw new \Gedmo\Exception\InvalidArgumentException('ObjectManager does not support filters');
    }
}
