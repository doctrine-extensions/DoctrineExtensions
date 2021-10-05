<?php

declare(strict_types=1);

namespace Gedmo\Mapping\Annotation;

use Mapping\Fixture\Annotation\TranslatableModel as AnnotationTranslatableModel;
use Mapping\Fixture\Attribute\TranslatableModel as AttributeTranslatableModel;
use Tool\BaseTestAnnotation;

class TranslatableTest extends BaseTestAnnotation
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
