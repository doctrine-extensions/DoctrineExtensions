<?php

namespace Gedmo\Tests\Translatable\Fixture;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;

/**
 * @ORM\Entity
 */
#[ORM\Entity]
class Article implements Translatable
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private $id;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(name="title", type="string", length=128)
     */
    #[Gedmo\Translatable]
    #[ORM\Column(name: 'title', type: Types::STRING, length: 128)]
    private $title;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(name="content", type="text", nullable=true)
     */
    #[Gedmo\Translatable]
    #[ORM\Column(name: 'content', type: Types::TEXT, nullable: true)]
    private $content;

    /**
     * @Gedmo\Translatable(fallback=false)
     * @ORM\Column(name="views", type="integer", nullable=true)
     */
    #[Gedmo\Translatable(fallback: false)]
    #[ORM\Column(name: 'views', type: Types::INTEGER, nullable: true)]
    private $views;

    /**
     * @Gedmo\Translatable(fallback=true)
     * @ORM\Column(name="author", type="string", nullable=true)
     */
    #[Gedmo\Translatable(fallback: true)]
    #[ORM\Column(name: 'author', type: Types::STRING, nullable: true)]
    private $author;

    /**
     * Used locale to override Translation listener`s locale
     *
     * @Gedmo\Locale
     */
    #[Gedmo\Locale]
    private $locale;

    /**
     * @ORM\OneToMany(targetEntity="Comment", mappedBy="article")
     */
    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'article')]
    private $comments;

    public function getId()
    {
        return $this->id;
    }

    public function addComment(Comment $comment)
    {
        $comment->setArticle($this);
        $this->comments[] = $comment;
    }

    public function getComments()
    {
        return $this->comments;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setContent($content)
    {
        $this->content = $content;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setTranslatableLocale($locale)
    {
        $this->locale = $locale;
    }

    public function setViews($views)
    {
        $this->views = $views;
    }

    public function getViews()
    {
        return $this->views;
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
