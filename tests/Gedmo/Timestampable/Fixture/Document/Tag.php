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

/**
 * @ODM\EmbeddedDocument()
 */
#[ODM\EmbeddedDocument]
class Tag
{
    /**
     * @ODM\Field(type="string")
     *
     * @var string
     */
    #[ODM\Field(type: MongoDBType::STRING)]
    protected $name;

    /**
     * @ODM\Field(type="date")
     * @Gedmo\Timestampable(on="create")
     *
     * @var \DateTime
     */
    #[Gedmo\Timestampable(on: 'create')]
    #[ODM\Field(type: MongoDBType::DATE)]
    protected $created;

    /**
     * @ODM\Field(type="date")
     * @Gedmo\Timestampable
     *
     * @var \DateTime
     */
    #[Gedmo\Timestampable]
    #[ODM\Field(type: MongoDBType::DATE)]
    protected $updated;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    public function setCreated(\DateTime $created)
    {
        $this->created = $created;
    }

    /**
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    public function setUpdated(\DateTime $updated)
    {
        $this->updated = $updated;
    }
}
