<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Translatable\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Gedmo\Translatable\Document\Repository\TranslationRepository;

/**
 * Gedmo\Translatable\Document\Translation
 *
 * @ODM\Document(repositoryClass="Gedmo\Translatable\Document\Repository\TranslationRepository")
 * @ODM\UniqueIndex(name="lookup_unique_idx", keys={
 *         "locale" = "asc",
 *         "object_class" = "asc",
 *         "foreign_key" = "asc",
 *         "field" = "asc"
 * })
 * @ODM\Index(name="translations_lookup_idx", keys={
 *      "locale" = "asc",
 *      "object_class" = "asc",
 *      "foreign_key" = "asc"
 * })
 */
#[ODM\Document(repositoryClass: TranslationRepository::class)]
#[ODM\UniqueIndex(name: 'lookup_unique_idx', keys: ['locale' => 'asc', 'object_class' => 'asc', 'foreign_key' => 'asc', 'field' => 'asc'])]
#[ODM\Index(name: 'translations_lookup_idx', keys: ['locale' => 'asc', 'object_class' => 'asc', 'foreign_key' => 'asc'])]
class Translation extends MappedSuperclass\AbstractTranslation
{
    /*
     * All required columns are mapped through inherited superclass
     */
}
