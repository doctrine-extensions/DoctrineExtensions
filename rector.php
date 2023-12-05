<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromAssignsRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__.'/src',
        __DIR__.'/tests',
        __DIR__.'/example',
    ]);

    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_74,
    ]);

    $rectorConfig->skip([
        TypedPropertyFromAssignsRector::class => [
            __DIR__.'/src/Mapping/MappedEventSubscriber.php', // Rector is trying to set a type on the $annotationReader property which requires a union type, not supported on PHP 7.4
            __DIR__.'/tests/Gedmo/Wrapper/Fixture/Entity/CompositeRelation.php', // @todo: remove this when https://github.com/doctrine/orm/issues/8255 is solved
        ],
    ]);

    $rectorConfig->importNames();
    $rectorConfig->importShortClasses(false);
};
