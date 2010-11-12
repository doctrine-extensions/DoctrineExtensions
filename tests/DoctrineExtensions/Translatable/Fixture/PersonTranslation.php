<?php

namespace Translatable\Fixture;

use DoctrineExtensions\Translatable\Entity\AbstractTranslation;

/**
 * @Table(name="person_translations", indexes={
 *      @index(name="person_translation_idx", columns={"locale", "entity", "foreign_key", "field"})
 * })
 * @Entity(repositoryClass="DoctrineExtensions\Translatable\Repository\TranslationRepository")
 */
class PersonTranslation extends AbstractTranslation
{
    
}