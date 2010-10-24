<?php
namespace DoctrineExtensions\Tree;

/**
 * The configuration options for Tree behavior
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package DoctrineExtensions.Tree
 * @subpackage Configuration
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Configuration
{    
    /**
     * Left field name of tree
     * 
     * @var string
     */
    private $_left = 'lft';
    
    /**
     * Right field name of tree
     * 
     * @var string
     */
    private $_right = 'rgt';
    
    /**
     * Parent field name of tree
     * 
     * @var string
     */
    private $_parent = 'parent';
    
    /**
     * Set left field name of tree
     * 
     * @param string $left
     * @return DoctrineExtensions\Tree\Configuration
     */
    public function setLeftField($left)
    {
        $this->_left = $left;
        return $this;
    }
    
    /**
     * Get left field name of tree
     * 
     * @return string
     */
    public function getLeftField()
    {
        return $this->_left;
    }
    
    /**
     * Set right field name of tree
     * 
     * @param string $right
     * @return DoctrineExtensions\Tree\Configuration
     */
    public function setRightField($right)
    {
        $this->_right = $right;
        return $this;
    }
    
    /**
     * Get right field name of tree
     * 
     * @return string
     */
    public function getRightField()
    {
        return $this->_right;
    }
    
    /**
     * Set parent field name of tree
     * 
     * @param string $parent
     * @return DoctrineExtensions\Tree\Configuration
     */
    public function setParentField($parent)
    {
        $this->_parent = $parent;
        return $this;
    }
    
    /**
     * Get parent field name of tree
     * 
     * @return string
     */
    public function getParentField()
    {
        return $this->_parent;
    }
}