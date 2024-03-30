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

#[ODM\Document(collection: 'kids')]
class Kid
{
    /**
     * @var int|null
     */
    #[Gedmo\SortablePosition]
    #[ODM\Field(type: MongoDBType::INT)]
    protected $position;

    #[Gedmo\SortableGroup]
    #[ODM\Field(type: MongoDBType::DATE)]
    protected ?\DateTimeInterface $birthdate = null;

    /**
     * @var string|null
     */
    #[ODM\Id]
    private $id;

    #[ODM\Field(type: MongoDBType::STRING)]
    private ?string $lastname = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setLastname(?string $lastname): void
    {
        $this->lastname = $lastname;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setPosition(?int $position): void
    {
        $this->position = $position;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setBirthdate(\DateTimeInterface $birthdate): void
    {
        $this->birthdate = $birthdate;
    }

    public function getBirthdate(): ?\DateTimeInterface
    {
        return $this->birthdate;
    }
}
