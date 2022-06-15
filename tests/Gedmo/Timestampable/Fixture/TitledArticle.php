<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Timestampable\Fixture;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Timestampable;

/**
 * @ORM\Entity
 */
#[ORM\Entity]
class TitledArticle implements Timestampable
{
    /**
     * @var int|null
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="title", type="string", length=128)
     */
    #[ORM\Column(name: 'title', type: Types::STRING, length: 128)]
    private $title;

    /**
     * @var string|null
     *
     * @ORM\Column(name="text", type="string", length=128)
     */
    #[ORM\Column(name: 'text', type: Types::STRING, length: 128)]
    private $text;

    /**
     * @var string|null
     *
     * @ORM\Column(name="state", type="string", length=128)
     */
    #[ORM\Column(name: 'state', type: Types::STRING, length: 128)]
    private $state;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="chtext", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="change", field="text")
     */
    #[ORM\Column(name: 'chtext', type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Gedmo\Timestampable(on: 'change', field: 'text')]
    private $chText;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="chtitle", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="change", field="title")
     */
    #[ORM\Column(name: 'chtitle', type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Gedmo\Timestampable(on: 'change', field: 'title')]
    private $chTitle;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="closed", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="change", field="state", value={"Published", "Closed"})
     */
    #[ORM\Column(name: 'closed', type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Gedmo\Timestampable(on: 'change', field: 'state', value: ['Published', 'Closed'])]
    private $closed;

    public function setChText(\DateTime $chText): void
    {
        $this->chText = $chText;
    }

    public function getChText(): \DateTime
    {
        return $this->chText;
    }

    public function setChTitle(\DateTime $chTitle): void
    {
        $this->chTitle = $chTitle;
    }

    public function getChTitle(): \DateTime
    {
        return $this->chTitle;
    }

    public function setClosed(\DateTime $closed): void
    {
        $this->closed = $closed;
    }

    public function getClosed(): \DateTime
    {
        return $this->closed;
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

    public function setState(?string $state): void
    {
        $this->state = $state;
    }

    public function getState(): ?string
    {
        return $this->state;
    }
}
