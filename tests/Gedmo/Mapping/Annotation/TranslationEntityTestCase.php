<?php

declare(strict_types=1);

namespace Gedmo\Tests\Mapping\Annotation;

use Gedmo\Mapping\Annotation\TranslationEntity;
use Gedmo\Tests\Mapping\Fixture\Annotation\TranslationEntityModel as AnnotationTranslationEntityModel;
use Gedmo\Tests\Mapping\Fixture\Attribute\TranslationEntityModel as AttributeTranslationEntityModel;

final class TranslationEntityTestCase extends BaseClassAnnotationTestCase
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
