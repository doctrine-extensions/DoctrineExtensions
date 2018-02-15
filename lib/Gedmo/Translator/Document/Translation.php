<?php

namespace Gedmo\Translator\Document;

use Gedmo\Translator\Translation as BaseTranslation;
use Doctrine\ODM\MongoDB\Mapping\Annotations\MappedSuperclass;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Id;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Document translation class.
 *
 * @author  Konstantin Kudryashov <ever.zet@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 *
 * @MappedSuperclass
 */
abstract class Translation extends BaseTranslation
{
    /**
     * @Id
     */
    protected $id;

    /**
     * @var string $locale
     *
     * @ODM\Field(type="string")
     */
    protected $locale;

    /**
     * @var string $property
     *
     * @ODM\Field(type="string")
     */
    protected $property;

    /**
     * @var string $value
     *
     * @ODM\Field(type="string")
     */
    protected $value;

    /**
     * Get id
     *
     * @return integer $id
     */
    public function getId()
    {
        return $this->id;
    }
}
