<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Timestampable\Fixture\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Types\Type as MongoDBType;
use Gedmo\Mapping\Annotation as Gedmo;

#[ODM\EmbeddedDocument]
class Tag
{
    /**
     * @var string
     */
    #[ODM\Field(type: MongoDBType::STRING)]
    protected $name;

    /**
     * @var \DateTimeInterface
     */
    #[Gedmo\Timestampable(on: 'create')]
    #[ODM\Field(type: MongoDBType::DATE)]
    protected $created;

    /**
     * @var \DateTimeInterface
     */
    #[Gedmo\Timestampable]
    #[ODM\Field(type: MongoDBType::DATE)]
    protected $updated;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getCreated(): \DateTime
    {
        return $this->created;
    }

    public function setCreated(\DateTime $created): void
    {
        $this->created = $created;
    }

    public function getUpdated(): \DateTime
    {
        return $this->updated;
    }

    public function setUpdated(\DateTime $updated): void
    {
        $this->updated = $updated;
    }
}
