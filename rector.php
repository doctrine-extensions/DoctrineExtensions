<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Rector\Config\RectorConfig;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromAssignsRector;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/src',
        __DIR__.'/tests',
        __DIR__.'/example',
    ])
    ->withPhpVersion(PhpVersion::PHP_81)
    ->withPhpSets()
    ->withConfiguredRule(TypedPropertyFromAssignsRector::class, [])
    ->withSkip([
        ReadOnlyPropertyRector::class => [
            __DIR__.'/tests', // A lot of test fixtures have properties that aren't mutated, don't let Rector try to make them readonly though
        ],
        ClassPropertyAssignToConstructorPromotionRector::class => [
            __DIR__.'/tests/Gedmo/Wrapper/Fixture/Entity/CompositeRelation.php', // @todo: Remove this when https://github.com/doctrine/orm/issues/8255 is solved
        ],
        TypedPropertyFromAssignsRector::class => [
            __DIR__.'/tests/Gedmo/Blameable/Fixture/Entity/Company.php', // @todo: Remove this when fixing the configuration for the `Company::$created` property
            __DIR__.'/tests/Gedmo/Wrapper/Fixture/Entity/CompositeRelation.php', // @todo: Remove this when https://github.com/doctrine/orm/issues/8255 is solved
        ],
    ])
    ->withImportNames(true, true, false)
;
