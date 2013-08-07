<?php

namespace Gedmo\Fixture\Uploadable;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @Gedmo\Uploadable(pathMethod="getPath")
 */
class Image
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\Column(name="title", type="string")
     */
    private $title;

    /**
     * @ORM\Column(name="path", type="string", nullable=true)
     * @Gedmo\UploadableFilePath
     */
    private $filePath;

    /**
     * @ORM\Column(name="size", type="decimal", nullable=true)
     * @Gedmo\UploadableFileSize
     */
    private $size;

    /**
     * @ORM\Column(name="mime_type", type="string", nullable=true)
     * @Gedmo\UploadableFileMimeType
     */
    private $mime;

    private $useBasePath = false;

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

    public function getPath($basePath = null)
    {
        if ($this->useBasePath) {
            return $basePath.'/abc/def';
        }

        return __DIR__.'/../../temp/uploadable';
    }

    public function setMime($mime)
    {
        $this->mime = $mime;
    }

    public function getMime()
    {
        return $this->mime;
    }

    public function setSize($size)
    {
        $this->size = $size;
    }

    public function getSize()
    {
        return $this->size;
    }

    public function setUseBasePath($useBasePath)
    {
        $this->useBasePath = $useBasePath;
    }
}
