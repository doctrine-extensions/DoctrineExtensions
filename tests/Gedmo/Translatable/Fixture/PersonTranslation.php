<?php

namespace Translatable\Fixture;

use Gedmo\Translatable\Entity\AbstractTranslation;

/**
 * @Table(name="person_translations", indexes={
 *      @index(name="person_translation_idx", columns={"locale", "entity", "foreign_key", "field"})
 * })
 * @Entity(repositoryClass="Gedmo\Translatable\Repository\TranslationRepository")
 */
class PersonTranslation extends AbstractTranslation
{
    
}