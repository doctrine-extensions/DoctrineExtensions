<?php

namespace Gedmo\Mapping\Annotation;

use Attribute;
use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * Group annotation for SoftDeleteable extension
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 *
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target("CLASS")
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class SoftDeleteable
{
    /** @var string */
    public $fieldName = 'deletedAt';

    /** @var bool */
    public $timeAware = false;

    /** @var bool */
    public $hardDelete = true;

    /**
     *
     * @param string $fieldName
     * @param bool $timeAware
     * @param bool $hardDelete
     *
     * @return void
     */
    public function __construct($fieldName = 'deletedAt', $timeAware = false, $hardDelete = true)
    {
        $this->fieldName = $fieldName;
        $this->timeAware = $timeAware;
        $this->hardDelete = $hardDelete;
    }
}
