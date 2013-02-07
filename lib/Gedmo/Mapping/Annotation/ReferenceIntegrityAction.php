<?php

namespace Gedmo\Mapping\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * ReferenceIntegrity annotation for ReferenceIntegrity behavioral extension
 *
 * @Gedmo\ReferenceIntegrity(actions={
 *      @Gedmo\ReferenceIntegrityAction(property="some_property", action="nullify"),
 *      ...
 * })
 *
 * @Annotation
 *
 * @author Evert Harmeling <evert.harmeling@freshheads.com>
 * @package Gedmo.Mapping.Annotation
 * @subpackage ReferenceIntegrity
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class ReferenceIntegrityAction extends Annotation
{
    /**
     * @var string
     */
    public $field;

    /**
     * @var string
     */
    public $action;
}
