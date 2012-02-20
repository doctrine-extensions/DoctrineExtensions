<?php

namespace Gedmo\Tree\Document\MongoDB\Repository;

use Gedmo\Exception\InvalidArgumentException,
    Gedmo\Tree\Strategy,
    Gedmo\Tree\Strategy\ORM\MaterializedPath;

/**
 * The MaterializedPathRepository has some useful functions
 * to interact with MaterializedPath tree. Repository uses
 * the strategy used by listener
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Tree.Entity.Repository
 * @subpackage MaterializedPathRepository
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class MaterializedPathRepository extends AbstractTreeRepository
{
    /**
     * {@inheritdoc}
     */
    protected function validate()
    {
        return $this->listener->getStrategy($this->dm, $this->getClassMetadata()->name)->getName() === Strategy::MATERIALIZED_PATH;
    }
}