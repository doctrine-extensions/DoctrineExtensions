<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Sluggable\Fixture\Handler\People;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Sluggable\Handler\RelativeSlugHandler;

/**
 * @ORM\Entity
 */
#[ORM\Entity]
class UrilizedPerson
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
     * @ORM\Column(length=64)
     */
    #[ORM\Column(length: 64)]
    private ?string $name = null;

    /**
     * @Gedmo\Slug(handlers={
     *     @Gedmo\SlugHandler(class="Gedmo\Sluggable\Handler\RelativeSlugHandler", options={
     *         @Gedmo\SlugHandlerOption(name="relationField", value="occupation"),
     *         @Gedmo\SlugHandlerOption(name="relationSlugField", value="slug"),
     *         @Gedmo\SlugHandlerOption(name="separator", value="/"),
     *         @Gedmo\SlugHandlerOption(name="urilize", value=true),
     *     })
     * }, separator="-", updatable=true, fields={"name"})
     *
     * @ORM\Column(name="slug", type="string", length=64, unique=true)
     */
    #[Gedmo\Slug(separator: '-', updatable: true, fields: ['name'])]
    #[Gedmo\SlugHandler(class: RelativeSlugHandler::class, options: ['relationField' => 'occupation', 'relationSlugField' => 'slug', 'separator' => '/', 'urilize' => true])]
    #[ORM\Column(name: 'slug', type: Types::STRING, length: 64, unique: true)]
    private ?string $slug = null;

    /**
     * @ORM\ManyToOne(targetEntity="UrilizedOccupation")
     */
    #[ORM\ManyToOne(targetEntity: UrilizedOccupation::class)]
    private ?UrilizedOccupation $occupation = null;

    public function setOccupation(?UrilizedOccupation $occupation = null): void
    {
        $this->occupation = $occupation;
    }

    public function getOccupation(): ?UrilizedOccupation
    {
        return $this->occupation;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }
}
