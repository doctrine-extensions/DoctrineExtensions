<?php

namespace Gedmo\Translatable\Entity;

/**
 * Gedmo\Translatable\Entity\Translation
 *
 * @Table(
 *         name="ext_translations", 
 *         indexes={@index(name="translations_lookup_idx", columns={
 *             "locale", "entity", "foreign_key"
 *         })},
 *         uniqueConstraints={@UniqueConstraint(name="lookup_unique_idx", columns={
 *             "locale", "entity", "foreign_key", "field"
 *         })}
 * )
 * @Entity(repositoryClass="Gedmo\Translatable\Repository\TranslationRepository")
 */
class Translation extends AbstractTranslation
{
    /**
     * All required columns are mapped through inherited superclass
     */
}