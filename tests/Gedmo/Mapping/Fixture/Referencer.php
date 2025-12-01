<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping\Fixture;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ODM\Document(collection="test_referencers")
 */
#[ODM\Document(collection: 'test_referencers')]
class Referencer
{
    /**
     * @ODM\Id
     */
    #[ODM\Id]
    private ?string $id = null;

    /**
     * @var Collection<int, Referencing>
     *
     * @ODM\ReferenceMany(targetDocument="Gedmo\Tests\Mapping\Fixture\Referencing", mappedBy="referencer")
     */
    #[ODM\ReferenceMany(targetDocument: Referencing::class, mappedBy: 'referencer')]
    private Collection $referencedDocuments;

    /**
     * @var Collection<int, Referenced>
     *
     * @Gedmo\ReferenceMany(type="entity", class="Gedmo\Tests\Mapping\Fixture\Referenced", mappedBy="referencer")
     */
    #[Gedmo\ReferenceMany(type: 'entity', class: Referenced::class, mappedBy: 'referencer')]
    private Collection $referencedEntities;

    public function __construct()
    {
        $this->referencedDocuments = new ArrayCollection();
        $this->referencedEntities = new ArrayCollection();
    }
}
