<?php

namespace Uploadable\Fixture\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @Gedmo\Uploadable(appendNumber=true, pathMethod="getPath", callback="callbackMethod")
 */
class FileAppendNumberWithUploadableFileName
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
     * @Gedmo\UploadableFileName
     */
    private $fileName;

    public $callBackData = [];

    public function callbackMethod($fileInfo)
    {
        $this->callBackData = $fileInfo;
    }

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

    public function setFileName($fileName)
    {
        $this->fileName = $fileName;
    }

    public function getFileName()
    {
        return $this->fileName;
    }

    public function getPath()
    {
        return __DIR__.'/../../../../temp/uploadable';
    }
}
