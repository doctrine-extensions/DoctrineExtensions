<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Sluggable\Fixture\Issue116;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity]
#[ORM\Table(name: 'sta_country')]
class Country
{
    /**
     * @var int|null
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * @var string|null
     */
    #[ORM\Column(type: Types::STRING, length: 10, nullable: true)]
    private ?string $languageCode = null;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private ?string $originalName = null;

    /**
     * @var string|null
     */
    #[ORM\Column(type: Types::STRING, length: 50)]
    #[Gedmo\Slug(separator: '-', fields: ['originalName'])]
    private ?string $alias = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setOriginalName(?string $originalName): void
    {
        $this->originalName = $originalName;
    }

    public function getOriginalName(): ?string
    {
        return $this->originalName;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }
}
