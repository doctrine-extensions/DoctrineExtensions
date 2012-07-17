<?php

namespace Gedmo\Mapping\Driver;

use Gedmo\Mapping\Driver\AnnotationDriverInterface;

/**
 * This is an abstract class to implement common functionality
 * for extension annotation mapping drivers.
 *
 * @author     Derek J. Lambert <dlambert@dereklambert.com>
 * @package    Gedmo.Mapping.Driver
 * @subpackage AnnotationDriverInterface
 * @link       http://www.gediminasm.org
 * @license    MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
abstract class AbstractAnnotationDriver implements AnnotationDriverInterface
{
    /**
     * Annotation reader instance
     *
     * @var object
     */
    protected $reader;

    /**
     * Original driver if it is available
     */
    protected $_originalDriver = null;

    /**
     * List of types which are valid for extension
     *
     * @var array
     */
    protected $validTypes = array();

    /**
     * {@inheritDoc}
     */
    public function setAnnotationReader($reader)
    {
        $this->reader = $reader;
    }

    /**
     * Passes in the mapping read by original driver
     *
     * @param $driver
     *
     * @return void
     */
    public function setOriginalDriver($driver)
    {
        $this->_originalDriver = $driver;
    }

    /**
     * @param object $meta
     *
     * @return array
     */
    public function getMetaReflectionClass($meta)
    {
        $class = $meta->getReflectionClass();
        if (!$class) {
            // based on recent doctrine 2.3.0-DEV maybe will be fixed in some way
            // this happens when running annotation driver in combination with
            // static reflection services. This is not the nicest fix
            $class = new \ReflectionClass($meta->name);
        }
        return $class;
    }
}
