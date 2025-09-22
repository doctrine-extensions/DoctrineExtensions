<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Translatable\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Gedmo\Translatable\Document\MappedSuperclass\AbstractTranslation;
use Gedmo\Translatable\Document\Repository\TranslationRepository;

/**
 * Gedmo\Translatable\Document\Translation
 *
 * @ODM\Document(repositoryClass="Gedmo\Translatable\Document\Repository\TranslationRepository")
 * @ODM\UniqueIndex(name="lookup_unique_idx", keys={
 *     "foreign_key": "asc",
 *     "locale": "asc",
 *     "object_class": "asc",
 *     "field": "asc"
 * })
 */
#[ODM\Document(repositoryClass: TranslationRepository::class)]
#[ODM\UniqueIndex(name: 'lookup_unique_idx', keys: ['foreign_key' => 'asc', 'locale' => 'asc', 'object_class' => 'asc', 'field' => 'asc'])]
class Translation extends AbstractTranslation
{
    /*
     * All required columns are mapped through inherited superclass
     */
}
