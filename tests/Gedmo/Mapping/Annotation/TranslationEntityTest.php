<?php

declare(strict_types=1);

namespace Gedmo\Mapping\Annotation;

use Mapping\Fixture\Annotation\TranslationEntityModel as AnnotationTranslationEntityModel;
use Mapping\Fixture\Attribute\TranslationEntityModel as AttributeTranslationEntityModel;
use Tool\BaseTestClassAnnotation;

class TranslationEntityTest extends BaseTestClassAnnotation
{
    public function getValidParameters(): iterable
    {
        return [
            ['class', \stdClass::class],
        ];
    }

    protected function getAnnotationClass(): string
    {
        return TranslationEntity::class;
    }

    protected function getAttributeModelClass(): string
    {
        return AttributeTranslationEntityModel::class;
    }

    protected function getAnnotationModelClass(): string
    {
        return AnnotationTranslationEntityModel::class;
    }
}
