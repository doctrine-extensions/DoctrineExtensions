<?php

namespace Tree\Fixture;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity(repositoryClass="Tree\Fixture\Repository\BehavioralCategoryRepository")
 * @Gedmo\Tree(type="nested")
 */
class BehavioralCategory
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(length=64)
     */
    private $title;

    /**
     * @Gedmo\TreeLeft
     * @ORM\Column(type="integer", nullable=true)
     */
    private $lft;

    /**
     * @Gedmo\TreeRight
     * @ORM\Column(type="integer", nullable=true)
     */
    private $rgt;

    /**
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="BehavioralCategory", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity="BehavioralCategory", mappedBy="parent")
     */
    private $children;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(length=128, nullable=true)
     */
    private $slug;

    /**
     * @ORM\OneToMany(targetEntity="BehavioralCategoryTranslation", mappedBy="object", cascade={"all"})
     */
    private $translations;

    public function __construct()
    {
        $this->translations = new ArrayCollection;
    }

    public function getTranslations()
    {
        return $this->translations;
    }

    public function addTranslation(BehavioralCategoryTranslation $translation)
    {
        if (!$this->translations->contains($translation)) {
            $this->translations[] = $translation;
            $translation->setObject($this);
        }
        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getSlug()
    {
        return $this->slug;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setParent(BehavioralCategory $parent = null)
    {
        $this->parent = $parent;
    }

    public function getParent()
    {
        return $this->parent;
    }
}
