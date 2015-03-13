<?php
namespace Gedmo\Translatable;

/**
 * @author Samuel ROZE <samuel.roze@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
interface LocaleResolverInterface
{
    /**
     * Get the default locale.
     *
     * This default locale can change based on the object given in parameter.
     *
     * @param object|null $object
     * @param object|null $meta
     * @return string
     */
    public function getDefaultLocale($object = null, $meta = null);
}
