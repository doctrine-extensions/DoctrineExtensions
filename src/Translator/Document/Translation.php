<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Translator\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Id;
use Doctrine\ODM\MongoDB\Mapping\Annotations\MappedSuperclass;
use Gedmo\Translator\Translation as BaseTranslation;

/**
 * Document translation class.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
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
     * @var string
     *
     * @ODM\Field(type="string")
     */
    protected $locale;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    protected $property;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    protected $value;

    /**
     * Get id
     *
     * @return int $id
     */
    public function getId()
    {
        return $this->id;
    }
}
