<?php

namespace Gedmo\Mapping;


use Doctrine\Common\Persistence\Mapping\ClassMetadata;

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
     * @param ClassMetadata $meta
     * @param array $config
     * @return void
     */
    public function readExtendedMetadata(ClassMetadata $meta, array &$config);

    /**
     * Callback triggered from driver then metadata is
     * fully formed from inherited classes if there were
     * any.
     *
     * @param ClassMetadata $meta
     * @param array $config
     * @return void
     */
    public function validateFullMetadata(ClassMetadata $meta, array $config);

    /**
     * Passes in the original driver
     *
     * @param $driver
     * @return void
     */
    public function setOriginalDriver($driver);
}
