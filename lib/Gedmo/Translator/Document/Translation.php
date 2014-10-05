<?php

namespace Gedmo\Translator\Document;

use Gedmo\Translator\Translation as BaseTranslation;
use Doctrine\ODM\MongoDB\Mapping\Annotations\MappedSuperclass;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Id;
use Doctrine\ODM\MongoDB\Mapping\Annotations\String as MongoString;

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
     * @MongoString
     */
    protected $locale;

    /**
     * @var string $property
     *
     * @MongoString
     */
    protected $property;

    /**
     * @var string $value
     *
     * @MongoString
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
