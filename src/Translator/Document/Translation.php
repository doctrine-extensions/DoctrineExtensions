<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Translator\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Types\Type;
use Gedmo\Translator\Translation as BaseTranslation;

/**
 * Document translation class.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * @ODM\MappedSuperclass
 */
#[ODM\MappedSuperclass]
abstract class Translation extends BaseTranslation
{
    /**
     * @var string|null
     *
     * @ODM\Id
     */
    #[ODM\Id]
    protected $id;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string")
     */
    #[ODM\Field(type: Type::STRING)]
    protected $locale;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string")
     */
    #[ODM\Field(type: Type::STRING)]
    protected $property;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string")
     */
    #[ODM\Field(type: Type::STRING)]
    protected $value;

    /**
     * @return string|null $id
     */
    public function getId()
    {
        return $this->id;
    }
}
