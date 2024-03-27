<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Rector\Config\RectorConfig;
use Rector\Doctrine\CodeQuality\Rector\Property\TypedPropertyFromToOneRelationTypeRector;
use Rector\Doctrine\Set\DoctrineSetList;
use Rector\Php81\Rector\Array_\FirstClassCallableRector;
use Rector\Set\ValueObject\LevelSetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__.'/src',
        __DIR__.'/tests',
        __DIR__.'/example',
    ]);

    $rectorConfig->sets([
        DoctrineSetList::DOCTRINE_CODE_QUALITY,
        DoctrineSetList::ANNOTATIONS_TO_ATTRIBUTES,
        DoctrineSetList::GEDMO_ANNOTATIONS_TO_ATTRIBUTES,
        DoctrineSetList::MONGODB__ANNOTATIONS_TO_ATTRIBUTES,
        LevelSetList::UP_TO_PHP_81,
    ]);

    $rectorConfig->skip([
        // https://github.com/rectorphp/rector/issues/8578
        FirstClassCallableRector::class,

        // https://github.com/rectorphp/rector-doctrine/issues/305
        TypedPropertyFromToOneRelationTypeRector::class,
    ]);

    $rectorConfig->importNames();
    $rectorConfig->importShortClasses(false);
};
