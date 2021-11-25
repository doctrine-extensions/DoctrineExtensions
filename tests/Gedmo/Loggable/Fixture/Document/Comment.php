<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Loggable\Fixture\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ODM\Document
 * @Gedmo\Loggable(logEntryClass="Gedmo\Tests\Loggable\Fixture\Document\Log\Comment")
 */
class Comment
{
    /**
     * @ODM\Id
     */
    private $id;

    /**
     * @Gedmo\Versioned
     * @ODM\Field(type="string")
     */
    private $subject;

    /**
     * @Gedmo\Versioned
     * @ODM\Field(type="string")
     */
    private $message;

    /**
     * @Gedmo\Versioned
     * @ODM\ReferenceOne(targetDocument="Gedmo\Tests\Loggable\Fixture\Document\RelatedArticle", inversedBy="comments")
     */
    private $article;

    /**
     * @ODM\EmbedOne(targetDocument="Gedmo\Tests\Loggable\Fixture\Document\Author")
     * @Gedmo\Versioned
     */
    private $author;

    public function setArticle($article)
    {
        $this->article = $article;
    }

    public function getArticle()
    {
        return $this->article;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setSubject($subject)
    {
        $this->subject = $subject;
    }

    public function getSubject()
    {
        return $this->subject;
    }

    public function setMessage($message)
    {
        $this->message = $message;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function setAuthor($author)
    {
        $this->author = $author;
    }

    public function getAuthor()
    {
        return $this->author;
    }
}
