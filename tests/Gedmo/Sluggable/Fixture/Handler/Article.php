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
use Gedmo\Sluggable\Sluggable;

#[ORM\Entity]
class Article implements Sluggable
{
    /**
     * @var int|null
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(name: 'title', type: Types::STRING, length: 64)]
    private ?string $title = null;

    #[ORM\Column(name: 'code', type: Types::STRING, length: 16)]
    private ?string $code = null;

    /**
     * @var string|null
     */
    #[Gedmo\Slug(separator: '-', updatable: true, fields: ['title', 'code'])]
    #[Gedmo\SlugHandler(class: InversedRelativeSlugHandler::class, options: ['relationClass' => ArticleRelativeSlug::class, 'mappedBy' => 'article', 'inverseSlugField' => 'slug'])]
    #[ORM\Column(name: 'slug', type: Types::STRING, length: 64, unique: true)]
    private ?string $slug = null;

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
