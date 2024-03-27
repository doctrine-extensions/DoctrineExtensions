<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Tree\Fixture\Closure;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 *  *
 * @author Anatoly Marinescu <tolean@zingan.com>
 */
#[ORM\Entity]
class News
{
    /**
     * @var int|null
     *
     *                */
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    public function __construct(#[ORM\Column(name: 'title', type: Types::STRING, length: 64)]
        private string $title, #[ORM\OneToOne(targetEntity: Category::class, cascade: ['persist'])]
        #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id')]
        private Category $category)
    {
    }
}
