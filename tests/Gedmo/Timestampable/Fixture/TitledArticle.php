<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Timestampable\Fixture;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Timestampable;

/**
 * @ORM\Entity
 */
#[ORM\Entity]
class TitledArticle implements Timestampable
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
     * @ORM\Column(name="title", type="string", length=128)
     */
    #[ORM\Column(name: 'title', type: Types::STRING, length: 128)]
    private $title;

    /**
     * @ORM\Column(name="text", type="string", length=128)
     */
    #[ORM\Column(name: 'text', type: Types::STRING, length: 128)]
    private $text;

    /**
     * @ORM\Column(name="state", type="string", length=128)
     */
    #[ORM\Column(name: 'state', type: Types::STRING, length: 128)]
    private $state;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="chtext", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="change", field="text")
     */
    #[ORM\Column(name: 'chtext', type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Gedmo\Timestampable(on: 'change', field: 'text')]
    private $chText;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="chtitle", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="change", field="title")
     */
    #[ORM\Column(name: 'chtitle', type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Gedmo\Timestampable(on: 'change', field: 'title')]
    private $chTitle;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="closed", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="change", field="state", value={"Published", "Closed"})
     */
    #[ORM\Column(name: 'closed', type: TYPES::DATETIME_MUTABLE, nullable: true)]
    #[Gedmo\Timestampable(on: 'change', field: 'state', value: ['Published', 'Closed'])]
    private $closed;

    /**
     * @param \DateTime $chText
     */
    public function setChText($chText)
    {
        $this->chText = $chText;
    }

    /**
     * @return \DateTime
     */
    public function getChText()
    {
        return $this->chText;
    }

    /**
     * @param \DateTime $chTitle
     */
    public function setChTitle($chTitle)
    {
        $this->chTitle = $chTitle;
    }

    /**
     * @return \DateTime
     */
    public function getChTitle()
    {
        return $this->chTitle;
    }

    /**
     * @param \DateTime $closed
     */
    public function setClosed($closed)
    {
        $this->closed = $closed;
    }

    /**
     * @return \DateTime
     */
    public function getClosed()
    {
        return $this->closed;
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

    public function setState($state)
    {
        $this->state = $state;
    }

    public function getState()
    {
        return $this->state;
    }
}
