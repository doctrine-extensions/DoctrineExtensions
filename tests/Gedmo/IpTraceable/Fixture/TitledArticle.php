<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\IpTraceable\Fixture;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\IpTraceable\IpTraceable;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 */
#[ORM\Entity]
class TitledArticle implements IpTraceable
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
     * @ORM\Column(name="chtext", type="string", length=45, nullable=true)
     * @Gedmo\IpTraceable(on="change", field="text")
     */
    #[ORM\Column(name: 'chtext', type: Types::STRING, length: 45, nullable: true)]
    #[Gedmo\IpTraceable(on: 'change', field: 'text')]
    private $chtext;

    /**
     * @var string|null
     *
     * @ORM\Column(name="chtitle", type="string", length=45, nullable=true)
     * @Gedmo\IpTraceable(on="change", field="title")
     */
    #[ORM\Column(name: 'chtitle', type: Types::STRING, length: 45, nullable: true)]
    #[Gedmo\IpTraceable(on: 'change', field: 'title')]
    private $chtitle;

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
