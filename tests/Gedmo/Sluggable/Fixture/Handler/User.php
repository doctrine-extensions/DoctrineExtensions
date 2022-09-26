<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Sluggable\Fixture\Handler;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Sluggable\Handler\RelativeSlugHandler;

/**
 * @ORM\Entity
 */
#[ORM\Entity]
class User
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
     * @ORM\Column(length=64)
     */
    #[ORM\Column(length: 64)]
    private $username;

    /**
     * @var string|null
     *
     * @Gedmo\Slug(handlers={
     *     @Gedmo\SlugHandler(class="Gedmo\Sluggable\Handler\RelativeSlugHandler", options={
     *         @Gedmo\SlugHandlerOption(name="relationField", value="company"),
     *         @Gedmo\SlugHandlerOption(name="relationSlugField", value="alias"),
     *         @Gedmo\SlugHandlerOption(name="separator", value="/")
     *     })
     * }, separator="-", updatable=true, fields={"username"})
     * @ORM\Column(length=64, unique=true)
     */
    #[Gedmo\Slug(separator: '-', updatable: true, fields: ['username'])]
    #[Gedmo\SlugHandler(class: RelativeSlugHandler::class, options: ['relationField' => 'company', 'relationSlugField' => 'alias', 'separator' => '/'])]
    #[ORM\Column(length: 64, unique: true)]
    private $slug;

    /**
     * @var Company|null
     *
     * @ORM\ManyToOne(targetEntity="Company")
     */
    #[ORM\ManyToOne(targetEntity: Company::class)]
    private $company;

    public function setCompany(Company $company = null): void
    {
        $this->company = $company;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setUsername(?string $username): void
    {
        $this->username = $username;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }
}
