<?php

namespace DoctrineExtensions\Sluggable;

/**
 * This interface must be implemented for all entities
 * to active the Sluggable behavior
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package DoctrineExtensions.Sluggable
 * @subpackage Sluggable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
interface Sluggable
{
	/**
	 * Specifies the configuration for slug generation
	 * 
	 * @see Sluggable\Configuration for options available
	 * @return Sluggable\Configuration
	 */
	public function getSluggableConfiguration();
}