<?php

declare(strict_types=1);

namespace Gedmo\Tests\Mapping\Fixture\Attribute;

use Gedmo\Mapping\Annotation as Gedmo;

#[Gedmo\TranslationEntity(class: \stdClass::class)]
class TranslationEntityModel
{
}
