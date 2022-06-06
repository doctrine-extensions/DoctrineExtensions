<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Translatable\Fixture;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 */
#[ORM\Entity]
class Comment
{
    /**
     * @var int|null
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private $id;

    /**
     * @var string|null
     *
     * @Gedmo\Translatable
     * @ORM\Column(name="subject", type="string", length=128)
     */
    #[Gedmo\Translatable]
    #[ORM\Column(name: 'subject', type: Types::STRING, length: 128)]
    private $subject;

    /**
     * @var string|null
     *
     * @Gedmo\Translatable
     * @ORM\Column(name="message", type="text")
     */
    #[Gedmo\Translatable]
    #[ORM\Column(name: 'message', type: Types::TEXT)]
    private $message;

    /**
     * @var Article|null
     *
     * @ORM\ManyToOne(targetEntity="Article", inversedBy="comments")
     */
    #[ORM\ManyToOne(targetEntity: Article::class, inversedBy: 'comments')]
    private $article;

    /**
     * @var string|null
     *
     * Used locale to override Translation listener`s locale
     *
     * @Gedmo\Language
     */
    #[Gedmo\Language]
    private $locale;

    public function setArticle(Article $article): void
    {
        $this->article = $article;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setSubject(?string $subject): void
    {
        $this->subject = $subject;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setMessage(?string $message): void
    {
        $this->message = $message;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setTranslatableLocale(?string $locale): void
    {
        $this->locale = $locale;
    }
}
