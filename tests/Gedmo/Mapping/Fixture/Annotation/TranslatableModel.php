<?php

declare(strict_types=1);

namespace Gedmo\Tests\Mapping\Fixture\Annotation;

use Gedmo\Mapping\Annotation as Gedmo;

class TranslatableModel
{
    /**
     * @Gedmo\Translatable()
     */
    private $title;

    /**
     * @Gedmo\Translatable(fallback=true)
     */
    private $titleFallbackTrue;

    /**
     * @Gedmo\Translatable(fallback=false)
     */
    private $titleFallbackFalse;
}
