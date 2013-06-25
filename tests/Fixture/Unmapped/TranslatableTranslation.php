<?php

namespace Fixture\Unmapped;

use Gedmo\Translatable\Entity\MappedSuperclass\AbstractTranslation;

class TranslatableTranslation extends AbstractTranslation
{
    protected $object;

    private $title;

    private $content;

    private $author;
}
