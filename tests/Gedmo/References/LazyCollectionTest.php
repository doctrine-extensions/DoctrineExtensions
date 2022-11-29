<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\References;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;

final class LazyCollectionTest extends TestCase
{
    public function testCallback(): void
    {
        $collection = new LazyCollection(static function (): Collection {
            return new ArrayCollection(['1', '2']);
        });

        static::assertCount(2, $collection);
    }
}
