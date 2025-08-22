<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\References;

use Doctrine\Common\Collections\AbstractLazyCollection;

/**
 * Lazy collection for loading reference many associations.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author Bulat Shakirzyanov <mallluhuct@gmail.com>
 * @author Jonathan H. Wage <jonwage@gmail.com>
 *
 * @template-extends AbstractLazyCollection<array-key, mixed>
 *
 * @final since gedmo/doctrine-extensions 3.11
 */
class LazyCollection extends AbstractLazyCollection
{
    /**
     * @var callable
     */
    private $callback;

    /**
     * @param callable $callback
     */
    public function __construct($callback)
    {
        $this->callback = $callback;
    }

    protected function doInitialize(): void
    {
        $this->collection = call_user_func($this->callback);
    }
}
