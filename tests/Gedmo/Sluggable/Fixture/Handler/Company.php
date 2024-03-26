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
use Gedmo\Sluggable\Handler\InversedRelativeSlugHandler;

#[ORM\Entity]
class Company
{
    /**
     * @var int|null
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(length: 64)]
    private ?string $title = null;

    /**
     * @var string|null
     */
    #[Gedmo\Slug(fields: ['title'])]
    #[Gedmo\SlugHandler(class: InversedRelativeSlugHandler::class, options: ['relationClass' => User::class, 'mappedBy' => 'company', 'inverseSlugField' => 'slug'])]
    #[ORM\Column(length: 64, unique: true)]
    private $alias;

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

    public function getAlias(): ?string
    {
        return $this->alias;
    }
}
