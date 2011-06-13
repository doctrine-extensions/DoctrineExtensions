<?php

namespace Translatable\Fixture\Issue75;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 */
class Article
{
    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    private $id;

    /**
     * @gedmo:Translatable
     * @Column(name="title", type="string", length=128)
     */
    private $title;

    /**
     * @ManyToMany(targetEntity="Image", inversedBy="articles")
     * @JoinTable(name="article_images",
     *      joinColumns={@JoinColumn(name="image_id", referencedColumnName="id")},
     *      inverseJoinColumns={@JoinColumn(name="article_id", referencedColumnName="id")}
     * )
     */
    private $images;

    /**
     * @ManyToMany(targetEntity="File")
     */
    private $files;

    public function __construct()
    {
        // $images is not an array, its a collection
        // if you want to do such operations you have to cunstruct it
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

    public function setImages(ArrayCollection $images)//Ok so here I just use an array collection in order to be coherent
    {
        foreach ($images as $newImage) {
            // first check if it does not contain it allready
            // because all entity objects are allready in memory
            // simply $em->find('Image', $id); and you will get it from this collection
            if (!$this->images->contains($newImage)) {
                $this->addImage($newImage);
            }
        }
		// i also need to remove previous images if applicable
		foreach($this->images as $oldImage) {
			if (!$images->contains($oldImage)) {
				$this->images->removeElement($oldImage);
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