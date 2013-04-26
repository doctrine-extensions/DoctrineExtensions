<?php

namespace Gedmo\Sluggable\Mapping\Event;

use Gedmo\Mapping\Event\AdapterInterface;

/**
 * Doctrine event adapter interface
 * for Sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
interface SluggableAdapter extends AdapterInterface
{
    /**
     * Loads the similar slugs
     *
     * @param object $object
     * @param object $meta
     * @param array $config
     * @param string $slug
     * @return array
     */
    function getSimilarSlugs($object, $meta, array $config, $slug);

    /**
     * Replace part of slug to all objects
     * matching $target pattern
     *
     * @param object $object
     * @param array $config
     * @param string $target
     * @param string $replacement
     * @return integer
     */
    function replaceRelative($object, array $config, $target, $replacement);

    /**
    * Replace part of slug to all objects
    * matching $target pattern and having $object
    * related
    *
    * @param object $object
    * @param array $config
    * @param string $target
    * @param string $replacement
    * @return integer
    */
    function replaceInverseRelative($object, array $config, $target, $replacement);
}