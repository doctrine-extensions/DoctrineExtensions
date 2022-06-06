<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Translatable\Fixture;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Embeddable
 */
#[ORM\Embeddable]
class CompanyEmbedLink
{
    /**
     * @var string
     *
     * @ORM\Column(name="website", type="string", length=191, nullable=true)
     * @Gedmo\Translatable
     */
    #[Gedmo\Translatable]
    #[ORM\Column(name: 'website', type: Types::STRING, length: 191, nullable: true)]
    protected $website;

    /**
     * @var string
     *
     * @ORM\Column(name="facebook", type="string", length=191, nullable=true)
     * @Gedmo\Translatable
     */
    #[Gedmo\Translatable]
    #[ORM\Column(name: 'facebook', type: Types::STRING, length: 191, nullable: true)]
    protected $facebook;

    public function getWebsite(): string
    {
        return $this->website;
    }

    public function setWebsite(string $website): self
    {
        $this->website = $website;

        return $this;
    }

    public function getFacebook(): string
    {
        return $this->facebook;
    }

    public function setFacebook(string $facebook): self
    {
        $this->facebook = $facebook;

        return $this;
    }
}
