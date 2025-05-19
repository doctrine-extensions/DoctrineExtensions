<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping\Fixture;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ODM\Document(collection="test_referencing")
 */
#[ODM\Document(collection: 'test_referencing')]
class Referencing
{
    /**
     * @ODM\Id
     */
    #[ODM\Id]
    private ?string $id = null;

    /**
     * @ODM\ReferenceOne(targetDocument="Gedmo\Tests\Mapping\Fixture\Referencer", mappedBy="referencedDocuments")
     *
     * @Gedmo\ReferenceIntegrity(value="nullify")
     */
    #[ODM\ReferenceOne(targetDocument: Referencer::class, mappedBy: 'referencedDocuments')]
    #[Gedmo\ReferenceIntegrity(value: 'nullify')]
    private ?Referencer $referencer = null;
}
