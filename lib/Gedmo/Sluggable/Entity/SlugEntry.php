<?php

namespace Gedmo\Sluggable\Entity;

use Doctrine\ORM\Mapping\Table,
    Doctrine\ORM\Mapping\Index,
    Doctrine\ORM\Mapping\Entity;

/**
 * Gedmo\Sluggable\Entity\SlugEntry
 *
 * @Table(
 *     name="ext_slug_entries",
 *  indexes={
 *      @index(name="slug_class_lookup_idx", columns={"slug", "object_class"}),
 *      @index(name="slug_date_lookup_idx", columns={"created"})
 *  }
 * )
 * @Entity(repositoryClass="Gedmo\Sluggable\Entity\Repository\SlugEntryRepository")
 *
 * @author Martin Jantosovic <jantosovic.martin@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class SlugEntry extends MappedSuperclass\AbstractSlugEntry {
    /**
     * All required columns are mapped through inherited superclass
     */
}
