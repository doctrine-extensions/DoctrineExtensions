<?php

namespace Gedmo\References;

use Doctrine\Common\Collections\Collection;

/**
 * Lazy collection for loading reference many associations.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author Bulat Shakirzyanov <mallluhuct@gmail.com>
 * @author Jonathan H. Wage <jonwage@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class LazyCollection implements Collection
{
    private $results;
    private $callback;

    public function __construct($callback)
    {
        $this->callback = $callback;
    }

    public function add($element)
    {
        $this->initialize();
        return $this->results->add($element);
    }

    public function clear()
    {
        $this->initialize();
        return $this->results->clear();
    }

    public function contains($element)
    {
        $this->initialize();
        return $this->results->contains($element);
    }

    public function containsKey($key)
    {
        $this->initialize();
        return $this->results->containsKey($key);
    }

    public function current()
    {
        $this->initialize();
        return $this->results->current();
    }

    public function exists(\Closure $p)
    {
        $this->initialize();
        return $this->results->exists($p);
    }

    public function filter(\Closure $p)
    {
        $this->initialize();
        return $this->results->filter($p);
    }

    public function first()
    {
        $this->initialize();
        return $this->results->first();
    }

    public function forAll(\Closure $p)
    {
        $this->initialize();
        return $this->results->forAll($p);
    }

    public function get($key)
    {
        $this->initialize();
        return $this->results->get($key);
    }

    public function getKeys()
    {
        $this->initialize();
        return $this->results->getKeys();
    }

    public function getValues()
    {
        $this->initialize();
        return $this->results->getValues();
    }

    public function indexOf($element)
    {
        $this->initialize();
        return $this->results->indexOf($element);
    }

    public function isEmpty()
    {
        $this->initialize();
        return $this->results->isEmpty();
    }

    public function key()
    {
        $this->initialize();
        return $this->results->key();
    }

    public function last()
    {
        $this->initialize();
        return $this->results->last();
    }

    public function map(\Closure $func)
    {
        $this->initialize();
        return $this->results->map($func);
    }

    public function next()
    {
        $this->initialize();
        return $this->results->next();
    }

    public function partition(\Closure $p)
    {
        $this->initialize();
        return $this->results->partition($p);
    }

    public function remove($key)
    {
        $this->initialize();
        return $this->results->remove($key);
    }

    public function removeElement($element)
    {
        $this->initialize();
        return $this->results->removeElement($element);
    }

    public function set($key, $value)
    {
        $this->initialize();
        return $this->results->set($key, $value);
    }

    public function slice($offset, $length = null)
    {
        $this->initialize();
        return $this->results->slice($offset, $length);
    }

    public function toArray()
    {
        $this->initialize();
        return $this->results->toArray();
    }

    public function offsetExists($offset)
    {
        $this->initialize();
        return $this->results->offsetExists($offset);
    }

    public function offsetGet($offset)
    {
        $this->initialize();
        return $this->results->offsetGet($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->initialize();
        return $this->results->offsetSet($offset, $value);
    }

    public function offsetUnset($offset)
    {
        $this->initialize();
        return $this->results->offsetUnset($offset);
    }

    public function getIterator()
    {
        $this->initialize();
        return $this->results->getIterator();
    }

    public function count()
    {
        $this->initialize();
        return $this->results->count();
    }

    private function initialize()
    {
        if (null === $this->results) {
            $this->results = call_user_func($this->callback);
        }
    }
}
