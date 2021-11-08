<?php

declare(strict_types=1);

namespace Gedmo\Tests\Mapping\Annotation;

use Gedmo\Mapping\Annotation\Translatable;
use Gedmo\Tests\Mapping\Fixture\Annotation\TranslatableModel as AnnotationTranslatableModel;
use Gedmo\Tests\Mapping\Fixture\Attribute\TranslatableModel as AttributeTranslatableModel;

final class TranslatablePropertyTestCase extends BasePropertyAnnotationTestCase
{
    public function getValidParameters(): iterable
    {
        return [
            ['fallback', 'title', null],
            ['fallback', 'titleFallbackTrue', true],
            ['fallback', 'titleFallbackFalse', false],
        ];
    }

    protected function getAnnotationClass(): string
    {
        return Translatable::class;
    }

    protected function getAttributeModelClass(): string
    {
        return AttributeTranslatableModel::class;
    }

    protected function getAnnotationModelClass(): string
    {
        return AnnotationTranslatableModel::class;
    }
}
