<?php

namespace Gedmo\Fixture\Uploadable;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 * @Gedmo\Uploadable(appendNumber=true, pathMethod="getPath")
 */
class FileAppendNumber
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\Column(name="title", type="string", nullable=true)
     */
    private $title;

    /**
     * @ORM\Column(name="path", type="string")
     * @Gedmo\UploadableFilePath
     */
    private $filePath;

    /**
     * @ORM\ManyToOne(targetEntity="Article", inversedBy="files")
     * @ORM\JoinColumn(name="article_id", referencedColumnName="id")
     */
    private $article;


    public function getId()
    {
        return $this->id;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setFilePath($filePath)
    {
        $this->filePath = $filePath;
    }

    public function getFilePath()
    {
        return $this->filePath;
    }

    public function setArticle(Article $article)
    {
        $this->article = $article;
    }

    public function getArticle()
    {
        return $this->article;
    }

    public function getPath()
    {
        return __DIR__.'/../../temp/uploadable';
    }
}
