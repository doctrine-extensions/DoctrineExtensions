<?php

namespace Translatable\Fixture\Issue75;

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

    public function getId()
    {
        return $this->id;
    }

    public function addImage(Image $image)
    {
        $this->images[] = $image;
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