<?php

namespace Gedmo\Mapping\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * Slug history annotation for Sluggable behavioral extension
 *
 * @Annotation
 * @Target("CLASS")
 *
 * @author Martin Jantosovic <jantosovic.martin@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class SlugHistory extends Annotation
{
	/** @var string */
	public $slugEntryClass;
}
