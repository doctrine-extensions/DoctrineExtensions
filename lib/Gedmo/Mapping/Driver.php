<?php

namespace Gedmo\Mapping;

use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * The mapping driver abstract class, defines the
 * metadata extraction function common among
 * all drivers used on these extensions.
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Common.Mapping
 * @subpackage Driver
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
interface Driver
{    
    /**
     * Read extended metadata configuration for
     * a single mapped class
     * 
     * @param ClassMetadataInfo $meta
     * @param array $config
     * @return void
     */
    public function readExtendedMetadata(ClassMetadataInfo $meta, array &$config);
    
    /**
     * Callback triggered from driver then metadata is
     * fully formed from inherited classes if there were
     * any.
     * 
     * @param ClassMetadataInfo $meta
     * @param array $config
     * @return void
     */
    public function validateFullMetadata(ClassMetadataInfo $meta, array $config);
}