<?php
namespace Tree\Fixture;

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
     * @Column(name="title", type="string", length=128)
     */
    private $title;
    
    /**
     * @OneToMany(targetEntity="Comment", mappedBy="article")
     */
    private $comments;
    
    /**
     * @ManyToOne(targetEntity="Category", inversedBy="articles")
     */
    private $category;

    public function getId()
    {
        return $this->id;
    }

    public function setCategory($category)
    {
        $this->category = $category;
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
}