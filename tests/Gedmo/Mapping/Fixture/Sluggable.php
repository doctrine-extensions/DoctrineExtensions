<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping\Fixture;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Sluggable\Handler\RelativeSlugHandler;
use Gedmo\Sluggable\Handler\TreeSlugHandler;

/**
 * @ORM\Entity
 */
#[ORM\Entity]
class Sluggable
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
     * @ORM\Column(name="title", type="string", length=64)
     */
    #[ORM\Column(name: 'title', type: Types::STRING, length: 64)]
    private ?string $title = null;

    /**
     * @ORM\Column(name="code", type="string", length=16)
     */
    #[ORM\Column(name: 'code', type: Types::STRING, length: 16, nullable: true)]
    private ?string $code = null;

    /**
     * @ORM\Column(name="ean", type="string", length=13)
     */
    #[ORM\Column(name: 'ean', type: Types::STRING, length: 13, nullable: true)]
    private ?string $ean = null;

    /**
     * @var string|null
     *
     * @Gedmo\Slug(handlers={
     *     @Gedmo\SlugHandler(class="Gedmo\Sluggable\Handler\TreeSlugHandler", options={
     *         @Gedmo\SlugHandlerOption(name="parentRelationField", value="parent"),
     *         @Gedmo\SlugHandlerOption(name="separator", value="/")
     *     }),
     *     @Gedmo\SlugHandler(class="Gedmo\Sluggable\Handler\RelativeSlugHandler", options={
     *         @Gedmo\SlugHandlerOption(name="relationField", value="parent"),
     *         @Gedmo\SlugHandlerOption(name="relationSlugField", value="test"),
     *         @Gedmo\SlugHandlerOption(name="separator", value="-")
     *     })
     * }, separator="_", updatable=false, fields={"title", "ean", "code"}, style="camel")
     *
     * @ORM\Column(name="slug", type="string", length=64, unique=true)
     */
    #[Gedmo\Slug(separator: '_', updatable: false, fields: ['title', 'ean', 'code'], style: 'camel')]
    #[Gedmo\SlugHandler(class: TreeSlugHandler::class, options: ['parentRelationField' => 'parent', 'separator' => '/'])]
    #[Gedmo\SlugHandler(class: RelativeSlugHandler::class, options: ['relationField' => 'parent', 'relationSlugField' => 'test', 'separator' => '-'])]
    #[ORM\Column(name: 'slug', type: Types::STRING, length: 64, unique: true)]
    private $slug;

    /**
     * @var Sluggable|null
     *
     * @ORM\ManyToOne(targetEntity="Sluggable")
     */
    #[ORM\ManyToOne(targetEntity: self::class)]
    private $parent;

    /**
     * @var User|null
     *
     * @ORM\ManyToOne(targetEntity="User")
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    private $user;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setCode(?string $code): void
    {
        $this->code = $code;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }
}
