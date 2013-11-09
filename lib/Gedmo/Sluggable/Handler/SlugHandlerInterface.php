<?php

namespace Gedmo\Sluggable\Handler;

use Gedmo\Sluggable\SluggableListener;
use Gedmo\Sluggable\Mapping\Event\SluggableAdapter;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;

/**
 * Sluggable handler interface is a common pattern for all
 * slug handlers which can be attached to the sluggable listener.
 * Usage is intended only for internal access of sluggable.
 * Should not be used outside of sluggable extension
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
interface SlugHandlerInterface
{
    /**
     * Construct the slug handler
     *
     * @param SluggableListener $sluggable
     */
    public function __construct(SluggableListener $sluggable);

    /**
     * Callback on slug handlers before the decision
     * is made whether or not the slug needs to be
     * recalculated
     *
     * @param SluggableAdapter $ea
     * @param array $config
     * @param object $object
     * @param string $slug
     * @param boolean $needToChangeSlug
     * @return void
     */
    public function onChangeDecision(SluggableAdapter $ea, array &$config, $object, &$slug, &$needToChangeSlug);

    /**
     * Callback on slug handlers right after the slug is built
     *
     * @param SluggableAdapter $ea
     * @param array $config
     * @param object $object
     * @param string $slug
     * @return void
     */
    public function postSlugBuild(SluggableAdapter $ea, array &$config, $object, &$slug);

    /**
     * Callback for slug handlers on slug completion
     *
     * @param SluggableAdapter $ea
     * @param array $config
     * @param object $object
     * @param string $slug
     * @return void
     */
    public function onSlugCompletion(SluggableAdapter $ea, array &$config, $object, &$slug);

    /**
     * @return boolean whether or not this handler has already urlized the slug
     */
    public function handlesUrlization();

    /**
     * Validate handler options
     *
     * @param array $options
     * @param ClassMetadata $meta
     */
    public static function validate(array $options, ClassMetadata $meta);
}