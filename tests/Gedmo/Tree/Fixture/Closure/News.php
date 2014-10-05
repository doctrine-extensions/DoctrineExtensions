<?php
/**
 * File description
 *
 * @author Anatoly Marinescu <tolean@zingan.com>
 */

namespace Tree\Fixture\Closure;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class News
{
    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\Column(name="title", type="string", length=64)
     */
    private $title;

    /**
     * @ORM\OneToOne(targetEntity="Tree\Fixture\Closure\Category", cascade={"persist"})
     * @ORM\JoinColumn(name="category_id", referencedColumnName="id")
     */
    private $category;

    public function __construct($title, Category $category)
    {
        $this->title = $title;
        $this->category = $category;
    }
}
