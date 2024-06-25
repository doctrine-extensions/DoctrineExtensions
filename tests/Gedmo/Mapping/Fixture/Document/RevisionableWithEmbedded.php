<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping\Fixture\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Types\Type;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Revisionable\Document\Revision;

/**
 * @ODM\Document(collection="revisionables_with_embedded")
 *
 * @Gedmo\Revisionable(revisionClass="Gedmo\Revisionable\Document\Revision")
 */
#[ODM\Document(collection: 'revisionables_with_embedded')]
#[Gedmo\Revisionable(revisionClass: Revision::class)]
class RevisionableWithEmbedded
{
    /**
     * @ODM\Id
     */
    #[ODM\Id]
    private ?string $id = null;

    /**
     * @ODM\Field(type="string")
     *
     * @Gedmo\Versioned
     */
    #[ODM\Field(type: Type::STRING)]
    #[Gedmo\Versioned]
    private ?string $title = null;

    /**
     * @ODM\EmbedOne(targetDocument="Gedmo\Tests\Mapping\Fixture\Document\EmbeddedRevisionable")
     *
     * @Gedmo\Versioned
     */
    #[ODM\EmbedOne(targetDocument: EmbeddedRevisionable::class)]
    #[Gedmo\Versioned]
    private ?EmbeddedRevisionable $embedded = null;
}
