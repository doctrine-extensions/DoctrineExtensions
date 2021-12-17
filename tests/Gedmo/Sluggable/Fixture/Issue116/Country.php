<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Sluggable\Fixture\Issue116;

class Country
{
    /**
     * @var int|null
     */
    private $id;

    /**
     * @var string|null
     */
    private $languageCode;

    /**
     * @var string|null
     */
    private $originalName;

    /**
     * @var string|null
     */
    private $alias;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setOriginalName(?string $originalName): void
    {
        $this->originalName = $originalName;
    }

    public function getOriginalName(): ?string
    {
        return $this->originalName;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }
}
