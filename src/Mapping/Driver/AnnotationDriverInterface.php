<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Mapping\Driver;

use Doctrine\Common\Annotations\Reader;
use Gedmo\Mapping\Driver;

/**
 * Annotation driver interface, provides method
 * to set custom annotation reader.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
interface AnnotationDriverInterface extends Driver
{
    /**
     * Set the annotation reader instance
     *
     * When originally implemented, `Doctrine\Common\Annotations\Reader` was not available,
     * therefore this method may accept any object implementing these methods from the interface:
     *
     *     getClassAnnotations([reflectionClass])
     *     getClassAnnotation([reflectionClass], [name])
     *     getPropertyAnnotations([reflectionProperty])
     *     getPropertyAnnotation([reflectionProperty], [name])
     *
     * @param Reader|AttributeReader|object $reader
     *
     * @return void
     *
     * @note Providing any object is deprecated, as of 4.0 a `Doctrine\Common\Annotations\Reader` or `Gedmo\Mapping\Driver\AttributeReader` will be required
     */
    public function setAnnotationReader($reader);
}
