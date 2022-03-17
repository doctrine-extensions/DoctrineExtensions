<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Blameable\Traits;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Types\Type;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Blameable Trait, usable with PHP >= 5.4
 *
 * @author David Buchmann <mail@davidbu.ch>
 */
trait BlameableDocument
{
    /**
     * @var string
     * @Gedmo\Blameable(on="create")
     * @ODM\Field(type="string")
     * @Groups({
     *     "gedmo.doctrine_extentions.trait.blameable_document",
     *     "gedmo.doctrine_extentions.trait.blameable",
     *     "gedmo.doctrine_extentions.traits",
     * })
     */
    #[ODM\Field(type: Type::STRING)]
    #[Gedmo\Blameable(on: 'create')]
    #[Groups(["gedmo.doctrine_extentions.trait.blameable_document", "gedmo.doctrine_extentions.trait.blameable", "gedmo.doctrine_extentions.traits"])]
    protected $createdBy;

    /**
     * @var string
     * @Gedmo\Blameable(on="update")
     * @ODM\Field(type="string")
     * @Groups({
     *     "gedmo.doctrine_extentions.trait.blameable_document",
     *     "gedmo.doctrine_extentions.trait.blameable",
     *     "gedmo.doctrine_extentions.traits",
     * })
     */
    #[ODM\Field(type: Type::STRING)]
    #[Gedmo\Blameable(on: 'update')]
    #[Groups(["gedmo.doctrine_extentions.trait.blameable_document", "gedmo.doctrine_extentions.trait.blameable", "gedmo.doctrine_extentions.traits"])]
    protected $updatedBy;

    /**
     * Sets createdBy.
     *
     * @param string $createdBy
     *
     * @return $this
     */
    public function setCreatedBy($createdBy)
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * Returns createdBy.
     *
     * @return string
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * Sets updatedBy.
     *
     * @param string $updatedBy
     *
     * @return $this
     */
    public function setUpdatedBy($updatedBy)
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }

    /**
     * Returns updatedBy.
     *
     * @return string
     */
    public function getUpdatedBy()
    {
        return $this->updatedBy;
    }
}
