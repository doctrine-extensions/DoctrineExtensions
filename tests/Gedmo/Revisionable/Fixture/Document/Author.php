<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Revisionable\Fixture\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Types\Type;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Revisionable\Revisionable;

/**
 * @ODM\EmbeddedDocument
 *
 * @Gedmo\Revisionable
 */
#[ODM\EmbeddedDocument]
#[Gedmo\Revisionable]
class Author implements Revisionable
{
    /**
     * @ODM\Field(type="string")
     *
     * @Gedmo\Versioned
     */
    #[ODM\Field(type: Type::STRING)]
    #[Gedmo\Versioned]
    private ?string $name = null;

    /**
     * @ODM\Field(type="string")
     *
     * @Gedmo\Versioned
     */
    #[ODM\Field(type: Type::STRING)]
    #[Gedmo\Versioned]
    private ?string $email = null;

    public function __toString()
    {
        return (string) $this->getName();
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }
}
