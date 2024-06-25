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

/**
 * @author Fabian Sabau <fabian.sabau@socialbit.de>
 *
 * @ODM\EmbeddedDocument
 */
#[ODM\EmbeddedDocument]
class EmbeddedRevisionable
{
    /**
     * @ODM\Field(type="string")
     *
     * @Gedmo\Versioned
     */
    #[ODM\Field(type: Type::STRING)]
    #[Gedmo\Versioned]
    private ?string $subtitle = null;
}
