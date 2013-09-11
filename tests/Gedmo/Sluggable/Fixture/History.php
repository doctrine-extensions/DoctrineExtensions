<?php

namespace Sluggable\Fixture;

use Gedmo\Sluggable\Entity\MappedSuperclass\AbstractSlugEntry;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="test_article_slug_entries")
 * @ORM\Entity(repositoryClass="Gedmo\Sluggable\Entity\Repository\SlugEntryRepository")
 */
class History extends AbstractSlugEntry
{

}
