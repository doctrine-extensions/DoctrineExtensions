<?php
namespace DoctrineExtensions\Sluggable;

/**
 * The configuration options for Sluggable behavior
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package DoctrineExtensions.Sluggable
 * @subpackage Configuration
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

class Configuration
{
    /**
     * The standard slug style, example: my-slug-slugish
     */
    const SLUG_STYLE_STANDARD = 0;
    
    /**
     * The camelized slug style, example: My-Slug-Slugish
     */
    const SLUG_STYLE_CAMEL = 1;
    
    /**
     * True if slug should be unique
     * 
     * @var boolean
     */
    private $_unique = true;
    
    /**
     * The prefered length of slug
     * 
     * @var integer
     */
    private $_length = 128;
    
    /**
     * List of fields from which slug will be collected
     * 
     * @var array
     */
    private $_fields = array();
    
    /**
     * Name of slug field where slug will be stored
     * 
     * @var string
     */
    private $_slugField = '';
    
    /**
     * True if slug should be updated on change
     * of sluggable fields
     * 
     * @var boolean
     */
    private $_updatable = true;
    
    /**
     * The slug builder class and method
     * will be given 3 parameters to it:
     *     $text - text to slug
     *     $separator - slug separator
     *     $entity - entity being slugged
     * 
     * @var array
     */
    private $_builder = array('DoctrineExtensions\Sluggable\Util\Urlizer', 'urlize');
    
    /**
     * The slug style
     * 
     * @var integer
     */
    private $_slugStyle = 0;
    
    /**
     * The slug separator
     * 
     * @var string
     */
    private $_separator = '-';
    
    /**
     * Set slug unique mode
     * 
     * @param boolean $boolean
     * @return void
     */
    public function setIsUnique($boolean)
    {
        $this->_unique = (boolean)$boolean;
    }
    
    /**
     * Check if slug should be unique
     * 
     * @return boolean
     */
    public function isUnique()
    {
        return $this->_unique;
    }
    
    /**
     * Set the prefered length of slug
     * 
     * @param integer $length
     * @return void
     */
    public function setLength($length)
    {
        $this->_length = abs(intval($length));
    }
    
    /**
     * Get the prefered slug length
     * 
     * @return integer
     */
    public function getLength()
    {
        return $this->_length;
    }
    
    /**
     * Set the list of fields slug will be made of
     * 
     * @param array $fields
     * @return void
     */
    public function setSluggableFields($fields)
    {
        $this->_fields = (array)$fields;
    }
    
    /**
     * Get the sluggable fields
     * 
     * @return array
     */
    public function getSluggableFields()
    {
        return $this->_fields;
    }
    
    /**
     * Set the name of slug field
     * 
     * @param string $field
     * @return void
     */
    public function setSlugField($field)
    {
        $this->_slugField = (string)$field;
    }
    
    /**
     * Get the field where slug will be stored
     * 
     * @return string
     */
    public function getSlugField()
    {
        return $this->_slugField;
    }
    
    /**
     * Set the updatable mode
     * 
     * @param boolean $boolean
     * @return void
     */
    public function setIsUpdatable($boolean)
    {
        $this->_updatable = (boolean)$boolean;
    }
    
    /**
     * Check if slug is updatable
     * 
     * @return boolean
     */
    public function isUpdatable()
    {
        return $this->_updatable;
    }
    
    /**
     * Set the slug builder class and method
     * will be given 3 parameters to it:
     *     $text - text to slug
     *     $separator - slug separator
     *     $entity - entity being slugged
     * 
     * @param string $class
     * @param string $method
     * @return void
     */
    public function setSlugBuilder($class, $method)
    {
        $this->_builder = array($class => $method);
    }
    
    /**
     * Get the slug builder method and class
     * 
     * @return array
     */
    public function getSlugBuilder()
    {
        return $this->_builder;
    }
    
    /**
     * Set the slug style to use
     * 
     * @param integer $style
     * @return void
     */
    public function setSlugStyle($style)
    {
        $this->_slugStyle = intval($style);
    }
    
    /**
     * Get the slug style to use
     * 
     * @return integer
     */
    public function getSlugStyle()
    {
        return $this->_slugStyle;
    }
    
    /**
     * Set the slug separator
     * 
     * @param string $separator
     * @return void
     */
    public function setSeparator($separator)
    {
        $this->_separator = (string)$separator;
    }
    
    /**
     * Get the slug separator
     * 
     * @return string
     */
    public function getSeparator()
    {
        return $this->_separator;
    }
}