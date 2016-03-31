<?php

namespace Blameable\Fixture\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Does not follow the Article - Comment line by design
 * Used to test hybrid database implementations
 *
 * @see Gedmo\Blameable\BlameableHybridTest
 *
 * @ODM\Document(collection="logs")
 */
class Log
{
    /** @ODM\Id */
    private $id;

    /**
     * @ODM\String
     */
    private $content;

    /**
     * @var string $created
     *
     * @ODM\String
     * @Gedmo\Blameable(on="create")
     */
    private $created;

    /**
     * @var int $updated
     *
     * @ODM\Int
     * @Gedmo\Blameable
     */
    private $updated;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param mixed $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * @return string
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param string $created
     */
    public function setCreated($created)
    {
        $this->created = $created;
    }

    /**
     * @return string
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @param string $updated
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;
    }
}
