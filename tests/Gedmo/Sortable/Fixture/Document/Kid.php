<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Sortable\Fixture\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Types\Type as MongoDBType;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ODM\Document(collection="kids")
 */
#[ODM\Document(collection: 'kids')]
class Kid
{
    /** @ODM\Id */
    #[ODM\Id]
    private $id;

    /**
     * @Gedmo\SortablePosition
     * @ODM\Field(type="int")
     */
    #[Gedmo\SortablePosition]
    #[ODM\Field(type: MongoDBType::INT)]
    protected $position;

    /**
     * @Gedmo\SortableGroup
     * @ODM\Field(type="date")
     */
    #[Gedmo\SortableGroup]
    #[ODM\Field(type: MongoDBType::DATE)]
    protected $birthdate;

    /**
     * @ODM\Field(type="string")
     */
    #[ODM\Field(type: MongoDBType::STRING)]
    private $lastname;

    public function getId()
    {
        return $this->id;
    }

    public function setLastname($lastname)
    {
        $this->lastname = $lastname;
    }

    public function getLastname()
    {
        return $this->lastname;
    }

    public function setPosition($position)
    {
        $this->position = $position;
    }

    public function getPosition()
    {
        return $this->position;
    }

    public function setBirthdate(\DateTime $birthdate)
    {
        $this->birthdate = $birthdate;
    }

    public function getBirthdate()
    {
        return $this->birthdate;
    }
}
