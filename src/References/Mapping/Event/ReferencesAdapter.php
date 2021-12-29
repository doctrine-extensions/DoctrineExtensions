<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\References\Mapping\Event;

use Doctrine\Persistence\ObjectManager;
use Gedmo\Mapping\Event\AdapterInterface;

/**
 * Doctrine event adapter for the References extension.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author Bulat Shakirzyanov <mallluhuct@gmail.com>
 * @author Jonathan H. Wage <jonwage@gmail.com>
 */
interface ReferencesAdapter extends AdapterInterface
{
    /**
     * Gets the identifier of the given object using the provided object manager.
     *
     * @param ObjectManager $om
     * @param object        $object
     * @param bool          $single
     *
     * @return array|string|int|null array or single identifier
     */
    public function getIdentifier($om, $object, $single = true);

    /**
     * Gets a single reference from the provided object manager for a class and identifier.
     *
     * @param ObjectManager    $om
     * @param string           $class
     * @param array|string|int $identifier
     *
     * @phpstan-param class-string $class
     *
     * @return object|null
     */
    public function getSingleReference($om, $class, $identifier);

    /**
     * Extracts identifiers from an object or proxy using the provided object manager.
     *
     * @param ObjectManager $om
     * @param object        $object
     * @param bool          $single
     *
     * @return array|string|int|null array or single identifier
     */
    public function extractIdentifier($om, $object, $single = true);
}
