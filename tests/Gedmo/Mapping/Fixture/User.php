<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping\Fixture;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Tests\Mapping\Mock\Extension\Encoder\Mapping as Ext;
use Gedmo\Tests\Translatable\Fixture\PersonTranslation;

#[ORM\Table(name: 'users')]
#[ORM\Entity]
#[ORM\Index(columns: ['username'], name: 'search_idx')]
#[Gedmo\TranslationEntity(class: PersonTranslation::class)]
class User
{
    /**
     * @var int|null
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[Ext\Encode(type: 'sha1', secret: 'xxx')]
    #[ORM\Column(length: 64)]
    private ?string $name = null;

    #[Ext\Encode(type: 'md5')]
    #[ORM\Column(length: 32)]
    #[Gedmo\Translatable]
    private ?string $password = null;

    #[ORM\Column(length: 128)]
    #[Gedmo\Translatable]
    private ?string $username = null;

    #[ORM\Column(length: 128, nullable: true)]
    #[Gedmo\Translatable(fallback: true)]
    private ?string $company = null;

    /**
     * @var string
     */
    #[Gedmo\Locale]
    private $localeField;

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setPassword(?string $password): void
    {
        $this->password = $password;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setCompany(string $company): void
    {
        $this->company = $company;
    }

    public function getCompany(): string
    {
        return $this->company;
    }
}
