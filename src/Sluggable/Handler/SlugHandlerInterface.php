<?php

namespace Gedmo\Sluggable\Handler;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Gedmo\Sluggable\Mapping\Event\SluggableAdapter;
use Gedmo\Sluggable\SluggableListener;

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
     */
    public function __construct(SluggableListener $sluggable);

    /**
     * Callback on slug handlers before the decision
     * is made whether or not the slug needs to be
     * recalculated
     *
     * @param object $object
     * @param string $slug
     * @param bool   $needToChangeSlug
     *
     * @return void
     */
    public function onChangeDecision(SluggableAdapter $ea, array &$config, $object, &$slug, &$needToChangeSlug);

    /**
     * Callback on slug handlers right after the slug is built
     *
     * @param object $object
     * @param string $slug
     *
     * @return void
     */
    public function postSlugBuild(SluggableAdapter $ea, array &$config, $object, &$slug);

    /**
     * Callback for slug handlers on slug completion
     *
     * @param object $object
     * @param string $slug
     *
     * @return void
     */
    public function onSlugCompletion(SluggableAdapter $ea, array &$config, $object, &$slug);

    /**
     * @return bool whether or not this handler has already urlized the slug
     */
    public function handlesUrlization();

    /**
     * Validate handler options
     */
    public static function validate(array $options, ClassMetadata $meta);
}
