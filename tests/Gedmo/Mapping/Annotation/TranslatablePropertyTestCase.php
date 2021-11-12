<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
