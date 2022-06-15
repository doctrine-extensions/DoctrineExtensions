<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Translatable\Fixture\Issue1123;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;

/**
 * @ORM\Entity
 * @ORM\Table("child_entity")
 */
#[ORM\Entity]
#[ORM\Table(name: 'child_entity')]
class ChildEntity extends BaseEntity implements Translatable
{
    /**
     * @var string|null
     *
     * @Gedmo\Translatable
     * @ORM\Column(name="childTitle", type="string", length=128, nullable=true)
     */
    #[ORM\Column(name: 'childTitle', type: Types::STRING, length: 128, nullable: true)]
    #[Gedmo\Translatable]
    private $childTitle;

    /**
     * @var string
     *
     * @Gedmo\Locale
     * Used locale to override Translation listener`s locale
     * this is not a mapped field of entity metadata, just a simple property
     */
    #[Gedmo\Locale]
    private $locale = 'en';

    public function getChildTitle(): ?string
    {
        return $this->childTitle;
    }

    public function setChildTitle(?string $childTitle): void
    {
        $this->childTitle = $childTitle;
    }

    public function setTranslatableLocale(string $locale): void
    {
        $this->locale = $locale;
    }
}
