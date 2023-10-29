<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Tree\Fixture\Repository;

use Gedmo\Tests\Tree\Fixture\BehavioralCategory;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;

/**
 * @template-extends NestedTreeRepository<BehavioralCategory>
 */
final class BehavioralCategoryRepository extends NestedTreeRepository
{
}
