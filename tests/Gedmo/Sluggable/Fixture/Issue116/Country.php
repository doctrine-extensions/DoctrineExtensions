<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Sluggable\Fixture\Issue116;

class Country
{
    private $id;
    private $languageCode;
    private $originalName;
    private $alias;

    public function getId()
    {
        return $this->id;
    }

    public function setOriginalName($originalName)
    {
        $this->originalName = $originalName;
    }

    public function getOriginalName()
    {
        return $this->originalName;
    }

    public function getAlias()
    {
        return $this->alias;
    }
}
