<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Blameable\Fixture\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Blameable;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 */
#[ORM\Entity]
class TitledArticle implements Blameable
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * @ORM\Column(name="title", type="string", length=128)
     */
    #[ORM\Column(name: 'title', type: Types::STRING, length: 128)]
    private ?string $title = null;

    /**
     * @ORM\Column(name="text", type="string", length=128)
     */
    #[ORM\Column(name: 'text', type: Types::STRING, length: 128)]
    private ?string $text = null;

    /**
     * @ORM\Column(name="chtext", type="string", nullable=true)
     *
     * @Gedmo\Blameable(on="change", field="text")
     */
    #[ORM\Column(name: 'chtext', type: Types::STRING, nullable: true)]
    #[Gedmo\Blameable(on: 'change', field: 'text')]
    private ?string $chtext = null;

    /**
     * @ORM\Column(name="chtitle", type="string", nullable=true)
     *
     * @Gedmo\Blameable(on="change", field="title")
     */
    #[ORM\Column(name: 'chtitle', type: Types::STRING, nullable: true)]
    #[Gedmo\Blameable(on: 'change', field: 'title')]
    private ?string $chtitle = null;

    public function setChtext(?string $chtext): void
    {
        $this->chtext = $chtext;
    }

    public function getChtext(): ?string
    {
        return $this->chtext;
    }

    public function setChtitle(?string $chtitle): void
    {
        $this->chtitle = $chtitle;
    }

    public function getChtitle(): ?string
    {
        return $this->chtitle;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setText(?string $text): void
    {
        $this->text = $text;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }
}
