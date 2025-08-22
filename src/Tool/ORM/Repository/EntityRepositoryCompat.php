<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tool\ORM\Repository;

use Doctrine\ORM\EntityRepository;

if ((new \ReflectionClass(EntityRepository::class))->getMethod('__call')->hasReturnType()) {
    // ORM 3.x
    /**
     * Helper trait to address compatibility issues between ORM 2.x and 3.x.
     *
     * @mixin EntityRepository
     *
     * @internal
     */
    trait EntityRepositoryCompat
    {
        /**
         * @phpstan-param list<mixed> $args
         */
        public function __call(string $method, array $args): mixed
        {
            return $this->doCallWithCompat($method, $args);
        }

        /**
         * @param string $method
         * @param array  $args
         *
         * @return mixed
         *
         * @phpstan-param list<mixed> $args
         */
        abstract protected function doCallWithCompat($method, $args);
    }
} else {
    // ORM 2.x
    /**
     * Helper trait to address compatibility issues between ORM 2.x and 3.x.
     *
     * @mixin EntityRepository
     *
     * @internal
     */
    trait EntityRepositoryCompat
    {
        /**
         * @param string $method
         * @param array  $args
         *
         * @return mixed
         *
         * @phpstan-param list<mixed> $args
         */
        public function __call($method, $args)
        {
            return $this->doCallWithCompat($method, $args);
        }

        /**
         * @param string $method
         * @param array  $args
         *
         * @return mixed
         *
         * @phpstan-param list<mixed> $args
         */
        abstract protected function doCallWithCompat($method, $args);
    }
}
