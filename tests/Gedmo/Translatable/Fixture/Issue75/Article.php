<?php

namespace Translatable\Fixture\Issue75;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Article
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(name="title", type="string", length=128)
     */
    private $title;

    /**
     * @ORM\ManyToMany(targetEntity="Image", inversedBy="articles")
     * @ORM\JoinTable(name="article_images",
     *      joinColumns={@ORM\JoinColumn(name="image_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="article_id", referencedColumnName="id")}
     * )
     */
    private $images;

    /**
     * @ORM\ManyToMany(targetEntity="File")
     */
    private $files;

    public function __construct()
    {
        // $images is not an array, its a collection
        // if you want to do such operations you have to construct it
        $this->images = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function addImage(Image $image)
    {
        $this->images[] = $image;
    }

    public function setImages(array $images)
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

    public function getImages()
    {
        return $this->images;
    }

    public function addFile(File $file)
    {
        $this->files[] = $file;
    }

    public function getFiles()
    {
        return $this->files;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }
}
