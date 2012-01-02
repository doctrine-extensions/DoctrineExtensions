<?php

namespace Gedmo\Sluggable\Handler;

use Doctrine\Common\Persistence\ObjectManager;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\Sluggable\Mapping\Event\SluggableAdapter;

/**
* Sluggable handler interface is a common pattern for all
* slug handlers which can be attached to the sluggable listener.
* Usage is intented only for internal access of sluggable.
* Should not be used outside of sluggable extension
*
* @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
* @package Gedmo.Sluggable.Handler
* @subpackage SlugHandlerInterface
* @link http://www.gediminasm.org
* @license MIT License (http://www.opensource.org/licenses/mit-license.php)
*/
interface SlugHandlerInterface
{
    /**
     * Construct the slug handler
     *
     * @param Gedmo\Sluggable\SluggableListener $sluggable
     */
    function __construct(SluggableListener $sluggable);

    /**
     * Callback on slug handlers before the decision
     * is made whether or not the slug needs to be
     * recalculated
     *
     * @param Gedmo\Sluggable\Mapping\Event\SluggableAdapter $ea
     * @param array $config
     * @param object $object
     * @param string $slug
     * @param boolean $needToChangeSlug
     * @return void
     */
    function onChangeDecision(SluggableAdapter $ea, $slugFieldConfig, $object, &$slug, &$needToChangeSlug);

    /**
     * Callback on slug handlers right after the slug is built
     *
     * @param Gedmo\Sluggable\Mapping\Event\SluggableAdapter $ea
     * @param array $config
     * @param object $object
     * @param string $slug
     * @return void
     */
    function postSlugBuild(SluggableAdapter $ea, array &$config, $object, &$slug);

    /**
     * Callback for slug handlers on slug completion
     *
     * @param Gedmo\Sluggable\Mapping\Event\SluggableAdapter $ea
     * @param array $config
     * @param object $object
     * @param string $slug
     * @return void
     */
    function onSlugCompletion(SluggableAdapter $ea, array &$config, $object, &$slug);

    /**
     * Validate handler options
     *
     * @param array $options
     */
    static function validate(array $options, $meta);
}