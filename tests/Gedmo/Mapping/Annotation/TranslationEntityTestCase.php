<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
