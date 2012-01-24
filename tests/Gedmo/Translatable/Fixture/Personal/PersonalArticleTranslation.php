<?php
namespace Translatable\Fixture\Personal;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Translatable\Entity\AbstractPersonalTranslation;

/**
 * @ORM\Table(name="article_translations")
 * @ORM\Entity(repositoryClass="Gedmo\Translatable\Entity\Repository\TranslationRepository")
 */
class PersonalArticleTranslation extends AbstractPersonalTranslation
{
    /**
     * @ORM\ManyToOne(targetEntity="Article", inversedBy="translations")
     */
    protected $entity;
}