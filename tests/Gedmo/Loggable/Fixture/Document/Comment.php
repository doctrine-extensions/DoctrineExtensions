<?php

namespace Loggable\Fixture\Document;

/**
 * @Document
 * @gedmo:Loggable(actions={"create", "delete"})
 */
class Comment
{
    /**
     * @Id
     */
    private $id;

    /**
     * @String
     */
    private $title;

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
}