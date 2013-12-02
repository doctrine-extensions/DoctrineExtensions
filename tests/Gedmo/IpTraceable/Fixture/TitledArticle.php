<?php
namespace IpTraceable\Fixture;

use Gedmo\IpTraceable\IpTraceable;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class TitledArticle implements IpTraceable
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(name="title", type="string", length=128)
     */
    private $title;

    /**
     * @ORM\Column(name="text", type="string", length=128)
     */
    private $text;

    /**
     * @var string $updated
     *
     * @ORM\Column(name="chtext", type="string", length=45, nullable=true)
     * @Gedmo\IpTraceable(on="change", field="text")
     */
    private $chtext;

    /**
     * @var string $chtitle
     *
     * @ORM\Column(name="chtitle", type="string", length=45, nullable=true)
     * @Gedmo\IpTraceable(on="change", field="title")
     */
    private $chtitle;

    /**
     * @param string $chtext
     */
    public function setChtext($chtext)
    {
        $this->chtext = $chtext;
    }

    /**
     * @return string
     */
    public function getChtext()
    {
        return $this->chtext;
    }

    /**
     * @param string $chtitle
     */
    public function setChtitle($chtitle)
    {
        $this->chtitle = $chtitle;
    }

    /**
     * @return string
     */
    public function getChtitle()
    {
        return $this->chtitle;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setText($text)
    {
        $this->text = $text;
    }

    public function getText()
    {
        return $this->text;
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
