<?php

namespace Translatable\Fixture;

use Translatable\Fixture\Template\ArticleTemplate;

/**
 * @Entity
 */
class TemplatedArticle extends ArticleTemplate
{
    /** 
     * @Id 
     * @GeneratedValue 
     * @Column(type="integer") 
     */
    private $id;
    
    /**
     * @gedmo:Translatable
     * @Column(type="string", length=128)
     */
    private $name;
    
    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }
}