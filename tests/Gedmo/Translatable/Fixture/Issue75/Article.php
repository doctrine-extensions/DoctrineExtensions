<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Translatable\Fixture\Issue75;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 */
#[ORM\Entity]
class Article
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
     * @Gedmo\Translatable
     * @ORM\Column(name="title", type="string", length=128)
     */
    #[Gedmo\Translatable]
    #[ORM\Column(name: 'title', type: Types::STRING, length: 128)]
    private $title;

    /**
     * @var Collection<int, Image>
     *
     * @ORM\ManyToMany(targetEntity="Image", inversedBy="articles")
     * @ORM\JoinTable(name="article_images",
     *     joinColumns={@ORM\JoinColumn(name="image_id", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="article_id", referencedColumnName="id")}
     * )
     */
    #[ORM\ManyToMany(targetEntity: Image::class, inversedBy: 'articles')]
    #[ORM\JoinTable(name: 'article_images')]
    #[ORM\JoinColumn(name: 'image_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'article_id', referencedColumnName: 'id')]
    private $images;

    /**
     * @var Collection<int, File>
     *
     * @ORM\ManyToMany(targetEntity="File")
     */
    #[ORM\ManyToMany(targetEntity: File::class)]
    private $files;

    public function __construct()
    {
        // $images is not an array, its a collection
        // if you want to do such operations you have to construct it
        $this->images = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function addImage(Image $image): void
    {
        $this->images[] = $image;
    }

    public function setImages(array $images): void
    {
        foreach ($images as $img) {
            // first check if it does not contain it allready
            // because all entity objects are allready in memory
            // simply $em->find('Image', $id); and you will get it from this collection
            if (!$this->images->contains($img)) {
                $this->addImage($img);
            }
        }
    }

    /**
     * @return Collection<int, Image>
     */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addFile(File $file): void
    {
        $this->files[] = $file;
    }

    /**
     * @return Collection<int, File>
     */
    public function getFiles(): Collection
    {
        return $this->files;
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
