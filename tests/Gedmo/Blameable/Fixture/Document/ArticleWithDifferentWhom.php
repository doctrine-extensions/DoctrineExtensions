<?php
namespace Blameable\Fixture\Document;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="articles")
 */
class ArticleWithDifferentWhom
{
    /** @ODM\Id */
    private $id;

    /**
     * @ODM\String
     */
    private $title;

    /**
     * @ODM\String
     * @Gedmo\Blameable(on="create")
     */
    private $createdUser;

    /**
     * @ODM\String
     * @Gedmo\Blameable(on="create", whom="consumer")
     */
    private $createdConsumer;

    /**
     * @ODM\String
     * @Gedmo\Blameable(on="update")
     */
    private $updatedUser;

    /**
     * @ODM\String
     * @Gedmo\Blameable(on="update", whom="consumer")
     */
    private $updatedConsumer;

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

    public function getCreatedUser()
    {
        return $this->createdUser;
    }

    public function getCreatedConsumer()
    {
        return $this->createdConsumer;
    }

    public function getUpdatedUser()
    {
        return $this->updatedUser;
    }

    public function getUpdatedConsumer()
    {
        return $this->updatedConsumer;
    }
}