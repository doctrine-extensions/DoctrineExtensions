<?php

namespace Gedmo\Tree\Entity\Repository;

use Doctrine\ORM\EntityRepository,
    Doctrine\ORM\EntityManager,
    Doctrine\ORM\Mapping\ClassMetadata;

/**
 * The TreeNodeRepository has some useful functions
 * to interact with tree.
 * 
 * Some Tree logic is copied from -
 * CakePHP: Rapid Development Framework (http://cakephp.org)
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Tree.Repository
 * @subpackage TreeNodeRepository
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TreeNodeRepository extends EntityRepository
{   
    /**
     * The tree repository adapter used
     * 
     * @var RepositoryAdapterInterface
     */ 
    protected $adapter = null;
    
	/**
     * {@inheritdoc}
     */
    public function __construct(EntityManager $em, ClassMetadata $class)
    {
        parent::__construct($em, $class);
        $evm = $em->getEventManager();
        $treeListener = null;
        foreach ($evm->getListeners() as $listener) {
            if ($listener instanceof \Gedmo\Tree\TreeListener) {
                $treeListener = $listener;
                break;
            }
        }
        
        if (is_null($treeListener)) {
            throw new \Gedmo\Exception\RuntimeException('Cannot find ORM tree listener attached');
        }
        
        $type = $treeListener->getStrategy()->getName();
        $repositoryAdapter = 'Gedmo\Tree\Entity\Repository\Adapter\\' . ucfirst($type);
        
        $this->adapter = new $repositoryAdapter($treeListener, $class, $em); 
    }
    
    /**
     * Get the Tree path of Nodes by given $node
     * 
     * @param object $node
     * @return array - list of Nodes in path
     */
    public function getPath($node)
    {
        return $this->adapter->getNodePathInTree($node);
    }
    
    /**
     * Counts the children of given TreeNode
     * 
     * @param object $node - if null counts all records in tree
     * @param boolean $direct - true to count only direct children
     * @return integer
     */ 
    public function childCount($node = null, $direct = false)
    {
        return $this->adapter->countChildren($node, $direct);
    }
    
    /**
     * Get list of children followed by given $node
     * 
     * @param object $node - if null, all tree nodes will be taken
     * @param boolean $direct - true to take only direct children
     * @param string $sortByField - field name to sort by
     * @param string $direction - sort direction : "ASC" or "DESC"
     * @throws InvalidArgumentException - if sort options are invalid
     * @return array - list of given $node children, null on failure
     */
    public function children($node = null, $direct = false, $sortByField = null, $direction = 'ASC')
    {
        return $this->adapter->getChildren($node, $direct, $sortByField, $direction);
    }
    
    /**
     * Get list of leaf nodes of the tree
     *
     * @param string $sortByField - field name to sort by
     * @param string $direction - sort direction : "ASC" or "DESC"
     * @throws InvalidArgumentException - if sort options are invalid
     * @return array - list of given $node children, null on failure
     */
    public function getLeafs($sortByField = null, $direction = 'ASC')
    {
        return $this->adapter->getTreeLeafs($sortByField, $direction);
    }
    
    /**
     * Move the node down in the same level
     * 
     * @param object $node
     * @param mixed $number
     *         integer - number of positions to shift
     *         boolean - true shift till last position
     * @throws RuntimeException - if something fails in transaction
     * @return boolean - true if shifted
     */
    public function moveDown($node, $number = 1)
    {
        return $this->adapter->moveNodeDown($node, $number);
    }
    
    /**
     * Move the node up in the same level
     * 
     * @param object $node
     * @param mixed $number
     *         integer - number of positions to shift
     *         boolean - true shift till first position
     * @throws RuntimeException - if something fails in transaction
     * @return boolean - true if shifted
     */
    public function moveUp($node, $number = 1)
    {
        return $this->adapter->moveNodeUp($node, $number);
    }
    
    /**
     * Reorders the sibling nodes and child nodes by given $node,
     * according to the $sortByField and $direction specified
     * 
     * @param object $node - null to reorder all tree
     * @param string $sortByField - field name to sort by
     * @param string $direction - sort direction : "ASC" or "DESC"
     * @param boolean $verify - true to verify tree first
     * @return boolean - true on success
     */
    public function reorder($node, $sortByField, $direction, $verify)
    {
        return $this->adapter->reorder($node, $sortByField, $direction, $verify);
    }
    
    /**
     * Removes given $node from the tree and reparents its descendants
     * 
     * @param object $node
     * @throws RuntimeException - if something fails in transaction
     * @return void
     */
    public function removeFromTree($node)
    {
        return $this->adapter->removeNodeFromTree($node);
    }
    
    /**
     * Verifies that current tree is valid.
     * If any error is detected it will return an array
     * with a list of errors found on tree
     * 
     * @return mixed
     *         boolean - true on success
     *         array - error list on failure
     */
    public function verify()
    {
        return $this->adapter->verifyTreeConsistence();
    }
    
    /**
     * Tries to recover the tree
     * 
     * @throws RuntimeException - if something fails in transaction
     * @return void
     */
    public function recover()
    {
        $this->adapter->recoverTree();
    }
}
