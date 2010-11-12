<?php

namespace DoctrineExtensions\Translatable\Entity;

/**
 * DoctrineExtensions\Translatable\Entity\Translation
 *
 * @Table(name="ext_translations", indexes={
 *      @index(name="lookup_idx", columns={"locale", "entity", "foreign_key", "field"})
 * })
 * @Entity(repositoryClass="DoctrineExtensions\Translatable\Repository\TranslationRepository")
 */
class Translation extends AbstractTranslation
{
    /**
     * All required columns are mapped through inherited superclass
     */
}