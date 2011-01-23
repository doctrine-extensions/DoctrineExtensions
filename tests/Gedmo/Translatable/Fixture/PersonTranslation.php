<?php

namespace Translatable\Fixture;

use Gedmo\Translatable\Entity\AbstractTranslation;

/**
 * @Table(
 *         name="ext_translations", 
 *         indexes={@index(name="translations_lookup_idx", columns={
 *             "locale", "object_class", "foreign_key"
 *         })},
 *         uniqueConstraints={@UniqueConstraint(name="lookup_unique_idx", columns={
 *             "locale", "object_class", "foreign_key", "field"
 *         })}
 * )
 * @Entity(repositoryClass="Gedmo\Translatable\Entity\Repository\TranslationRepository")
 */
class PersonTranslation extends AbstractTranslation
{
    
}