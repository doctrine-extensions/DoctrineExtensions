<?php

namespace Gedmo\Sluggable\Handler;

use Doctrine\Common\Persistence\ObjectManager;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\Sluggable\Mapping\Event\SluggableAdapter;
use Gedmo\Tool\Wrapper\AbstractWrapper;
use Gedmo\Exception\InvalidMappingException;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;

/**
* Sluggable handler which should be used in order to prefix
* a slug of related object. For instance user may belong to a company
* in this case user slug could look like 'company-name/user-firstname'
* where path separator separates the relative slug
*
* @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
* @license MIT License (http://www.opensource.org/licenses/mit-license.php)
*/
class RelativeSlugHandler implements SlugHandlerInterface
{
    const SEPARATOR = '/';

    /**
     * @var ObjectManager
     */
    protected $om;

    /**
     * @var SluggableListener
     */
    protected $sluggable;

    /**
     * Used options
     *
     * @var array
     */
    private $usedOptions;

    /**
     * Callable of original transliterator
     * which is used by sluggable
     *
     * @var callable
     */
    private $originalTransliterator;

    /**
     * $options = array(
     *     'separator' => '/',
     *     'relationField' => 'something',
     *     'relationSlugField' => 'slug'
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
    public function onChangeDecision(SluggableAdapter $ea, array &$config, $object, &$slug, &$needToChangeSlug)
    {
        $this->om = $ea->getObjectManager();
        $isInsert = $this->om->getUnitOfWork()->isScheduledForInsert($object);
        $this->usedOptions = $config['handlers'][get_called_class()];
        if (!isset($this->usedOptions['separator'])) {
            $this->usedOptions['separator'] = self::SEPARATOR;
        }
        if (!$isInsert && !$needToChangeSlug) {
            $changeSet = $ea->getObjectChangeSet($this->om->getUnitOfWork(), $object);
            if (isset($changeSet[$this->usedOptions['relationField']])) {
                $needToChangeSlug = true;
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function postSlugBuild(SluggableAdapter $ea, array &$config, $object, &$slug)
    {
        $this->originalTransliterator = $this->sluggable->getTransliterator();
        $this->sluggable->setTransliterator(array($this, 'transliterate'));
    }

    /**
     * {@inheritDoc}
     */
    public static function validate(array $options, ClassMetadata $meta)
    {
        if (!$meta->isSingleValuedAssociation($options['relationField'])) {
            throw new InvalidMappingException("Unable to find slug relation through field - [{$options['relationField']}] in class - {$meta->name}");
        }
    }

    /**
     * {@inheritDoc}
     */
    public function onSlugCompletion(SluggableAdapter $ea, array &$config, $object, &$slug)
    {
    }

    /**
     * Transliterates the slug and prefixes the slug
     * by relative one
     *
     * @param string $text
     * @param string $separator
     * @param object $object
     *
     * @return string
     */
    public function transliterate($text, $separator, $object)
    {
        $result = call_user_func_array(
            $this->originalTransliterator,
            array($text, $separator, $object)
        );
        $wrapped = AbstractWrapper::wrap($object, $this->om);
        $relation = $wrapped->getPropertyValue($this->usedOptions['relationField']);
        if ($relation) {
            $wrappedRelation = AbstractWrapper::wrap($relation, $this->om);
            $slug = $wrappedRelation->getPropertyValue($this->usedOptions['relationSlugField']);

            if (isset($this->usedOptions['urilize']) && $this->usedOptions['urilize']) {
                $slug = call_user_func_array(
                    $this->originalTransliterator,
                    array($slug, $separator, $object)
                );
            }

            $result = $slug.$this->usedOptions['separator'].$result;
        }
        $this->sluggable->setTransliterator($this->originalTransliterator);

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function handlesUrlization()
    {
        return true;
    }
}
