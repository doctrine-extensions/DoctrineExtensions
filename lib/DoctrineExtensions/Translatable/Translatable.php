<?php

namespace DoctrineExtensions\Translatable;

/**
 * This interface must be implemented for all entities
 * to active the Translatable behavior
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package DoctrineExtensions.Translatable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
interface Translatable
{
	/**
	 * Specifies the fields which should be translated.
	 * example: array('field1', 'field2')
	 * 
	 * @return array - list of translatable fields
	 */
	public function getTranslatableFields();
	
	/**
	 * Specifies the locale to be used for translation.
	 * example: en_us or lt_lt
	 * 
	 * @return string - locale to use
	 */
	public function getTranslatableLocale();
}