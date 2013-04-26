<?php

namespace Gedmo\Mapping\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * SlugHandler annotation for Sluggable behavioral extension
 *
 * @Gedmo\Slug(handlers={
 *      @Gedmo\SlugHandler(class="Some\Class", options={
 *          @Gedmo\SlugHandlerOption(name="relation", value="parent"),
 *          @Gedmo\SlugHandlerOption(name="separator", value="/")
 *      }),
 *      @Gedmo\SlugHandler(class="Some\Class", options={
 *          @Gedmo\SlugHandlerOption(name="option", value="val"),
 *          ...
 *      }),
 *      ...
 * }, separator="-", updatable=false)
 *
 * @Annotation
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class SlugHandler extends Annotation
{
    public $class = '';
    public $options = array();
}