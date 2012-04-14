<?php

namespace Uploadable\Fixture\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 * @Gedmo\Uploadable(pathMethod="getPath", fileInfoProperty="fileInfo", filenameGenerator="SHA1")
 */
class FileWithSha1Name
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\Column(name="path", type="string", nullable=true)
     * @Gedmo\UploadableFilePath
     */
    private $filePath;

    private $fileInfo;


    public function getId()
    {
        return $this->id;
    }

    public function setFilePath($filePath)
    {
        $this->filePath = $filePath;
    }

    public function getFilePath()
    {
        return $this->filePath;
    }

    public function setFileInfo(array $fileInfo)
    {
        $this->fileInfo = $fileInfo;
    }

    public function getFileInfo()
    {
        return $this->fileInfo;
    }

    public function getPath()
    {
        return __DIR__.'/../../../../temp/uploadable';
    }
}
