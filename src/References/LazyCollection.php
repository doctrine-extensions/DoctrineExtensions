<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\References;

use Doctrine\Common\Collections\Collection;

/**
 * Lazy collection for loading reference many associations.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author Bulat Shakirzyanov <mallluhuct@gmail.com>
 * @author Jonathan H. Wage <jonwage@gmail.com>
 *
 * @template-implements Collection<array-key, mixed>
 */
class LazyCollection implements Collection
{
    /**
     * @var Collection
     */
    private $results;

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

    /**
     * @return true
     */
    public function add($element)
    {
        $this->initialize();

        return $this->results->add($element);
    }

    /**
     * @return void
     */
    public function clear()
    {
        $this->initialize();

        $this->results->clear();
    }

    /**
     * @return bool
     */
    public function contains($element)
    {
        $this->initialize();

        return $this->results->contains($element);
    }

    /**
     * @return bool
     */
    public function containsKey($key)
    {
        $this->initialize();

        return $this->results->containsKey($key);
    }

    /**
     * @return mixed
     */
    public function current()
    {
        $this->initialize();

        return $this->results->current();
    }

    /**
     * @return bool
     */
    public function exists(\Closure $p)
    {
        $this->initialize();

        return $this->results->exists($p);
    }

    /**
     * @return Collection
     */
    public function filter(\Closure $p)
    {
        $this->initialize();

        return $this->results->filter($p);
    }

    /**
     * @return mixed
     */
    public function first()
    {
        $this->initialize();

        return $this->results->first();
    }

    /**
     * @return bool
     */
    public function forAll(\Closure $p)
    {
        $this->initialize();

        return $this->results->forAll($p);
    }

    /**
     * @return mixed
     */
    public function get($key)
    {
        $this->initialize();

        return $this->results->get($key);
    }

    /**
     * @return int[]|string[]
     */
    public function getKeys()
    {
        $this->initialize();

        return $this->results->getKeys();
    }

    /**
     * @return mixed[]
     */
    public function getValues()
    {
        $this->initialize();

        return $this->results->getValues();
    }

    /**
     * @return int|string|null
     */
    public function indexOf($element)
    {
        $this->initialize();

        return $this->results->indexOf($element);
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        $this->initialize();

        return $this->results->isEmpty();
    }

    /**
     * @return int|string|null
     */
    public function key()
    {
        $this->initialize();

        return $this->results->key();
    }

    /**
     * @return mixed
     */
    public function last()
    {
        $this->initialize();

        return $this->results->last();
    }

    /**
     * @return Collection
     */
    public function map(\Closure $func)
    {
        $this->initialize();

        return $this->results->map($func);
    }

    /**
     * @return mixed
     */
    public function next()
    {
        $this->initialize();

        return $this->results->next();
    }

    /**
     * @return Collection
     */
    public function partition(\Closure $p)
    {
        $this->initialize();

        return $this->results->partition($p);
    }

    /**
     * @return mixed
     */
    public function remove($key)
    {
        $this->initialize();

        return $this->results->remove($key);
    }

    /**
     * @return bool
     */
    public function removeElement($element)
    {
        $this->initialize();

        return $this->results->removeElement($element);
    }

    /**
     * @return void
     */
    public function set($key, $value)
    {
        $this->initialize();

        $this->results->set($key, $value);
    }

    /**
     * @return mixed[]
     */
    public function slice($offset, $length = null)
    {
        $this->initialize();

        return $this->results->slice($offset, $length);
    }

    /**
     * @return mixed[]
     */
    public function toArray()
    {
        $this->initialize();

        return $this->results->toArray();
    }

    /**
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        $this->initialize();

        return $this->results->offsetExists($offset);
    }

    /**
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        $this->initialize();

        return $this->results->offsetGet($offset);
    }

    /**
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        $this->initialize();

        $this->results->offsetSet($offset, $value);
    }

    /**
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        $this->initialize();

        $this->results->offsetUnset($offset);
    }

    /**
     * @return \Traversable
     */
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        $this->initialize();

        return $this->results->getIterator();
    }

    /**
     * @return int
     */
    #[\ReturnTypeWillChange]
    public function count()
    {
        $this->initialize();

        return $this->results->count();
    }

    private function initialize(): void
    {
        if (null === $this->results) {
            $this->results = call_user_func($this->callback);
        }
    }
}
