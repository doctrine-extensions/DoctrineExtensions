<?php

namespace Gedmo\References\Mapping\Event;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use Gedmo\Mapping\Event\AdapterInterface;

/**
 * Doctrine event adapter interface for References behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author Bulat Shakirzyanov <mallluhuct@gmail.com>
 * @author Jonathan H. Wage <jonwage@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
interface ReferencesAdapter extends AdapterInterface
{
    /**
     * Gets the identifier of the given object using the passed ObjectManager.
     *
     * @param ObjectManager $om
     * @param object $object
     * @param bool $single
     * @return array|scalar $id - array or single identifier
     */
    function getIdentifier($om, $object, $single = true);

    /**
     * Gets a single reference for the given ObjectManager, class and identifier.
     *
     * @param ObjectManager $om
     * @param string $class
     * @param array|scalar $identifier
     **/
    function getSingleReference($om, $class, $identifier);

    /**
     * Extracts identifiers from object or proxy.
     *
     * @param DocumentManager $om
     * @param object $object
     * @param bool $single
     * @return array|scalar - array or single identifier
     */
    function extractIdentifier($om, $object, $single = true);
}
