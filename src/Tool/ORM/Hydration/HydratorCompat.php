<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tool\ORM\Hydration;

use Doctrine\ORM\Internal\Hydration\AbstractHydrator;

// The methods we need the compat bridge for are protected, so we're using a public method for this check
if ((new \ReflectionClass(AbstractHydrator::class))->getMethod('onClear')->hasReturnType()) {
    // ORM 3.x
    /**
     * Helper trait to address compatibility issues between ORM 2.x and 3.x.
     *
     * @mixin AbstractHydrator
     *
     * @internal
     */
    trait HydratorCompat
    {
        /**
         * Executes one-time preparation tasks, once each time hydration is started
         * through {@link hydrateAll} or {@link toIterable()}.
         */
        protected function prepare(): void
        {
            $this->doPrepareWithCompat();
        }

        protected function doPrepareWithCompat(): void
        {
            parent::prepare();
        }

        /**
         * Executes one-time cleanup tasks at the end of a hydration that was initiated
         * through {@link hydrateAll} or {@link toIterable()}.
         */
        protected function cleanup(): void
        {
            $this->doCleanupWithCompat();
        }

        protected function doCleanupWithCompat(): void
        {
            parent::cleanup();
        }

        /**
         * Hydrates all rows from the current statement instance at once.
         */
        protected function hydrateAllData(): array
        {
            return $this->doHydrateAllData();
        }

        /**
         * @return mixed[]
         */
        protected function doHydrateAllData()
        {
            return parent::hydrateAllData();
        }
    }
} else {
    // ORM 2.x
    /**
     * Helper trait to address compatibility issues between ORM 2.x and 3.x.
     *
     * @mixin AbstractHydrator
     *
     * @internal
     */
    trait HydratorCompat
    {
        /**
         * Executes one-time preparation tasks, once each time hydration is started
         * through {@link hydrateAll} or {@link toIterable()}.
         *
         * @return void
         */
        protected function prepare()
        {
            $this->doPrepareWithCompat();
        }

        protected function doPrepareWithCompat(): void
        {
            parent::prepare();
        }

        /**
         * Executes one-time cleanup tasks at the end of a hydration that was initiated
         * through {@link hydrateAll} or {@link toIterable()}.
         *
         * @return void
         */
        protected function cleanup()
        {
            $this->doCleanupWithCompat();
        }

        protected function doCleanupWithCompat(): void
        {
            parent::cleanup();
        }

        /**
         * Hydrates all rows from the current statement instance at once.
         *
         * @return mixed[]
         */
        protected function hydrateAllData()
        {
            return $this->doHydrateAllData();
        }

        /**
         * @return mixed[]
         */
        protected function doHydrateAllData()
        {
            return parent::hydrateAllData();
        }
    }
}
