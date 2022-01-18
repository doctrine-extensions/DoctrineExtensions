<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Tree\Fixture;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;

/**
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
 * @ORM\Table(name="user")
 */
#[ORM\Entity(repositoryClass: NestedTreeRepository::class)]
#[ORM\Table(name: 'user')]
class User extends Role
{
    public const PASSWORD_SALT = 'dfJko$~346958rg!DFT]AEtzserf9giq)3/TAeg;aDFa43';

    /**
     * @var string|null
     *
     * @ORM\Column(name="email", type="string", unique=true)
     */
    #[ORM\Column(name: 'email', type: Types::STRING, unique: true)]
    private $email;

    /**
     * @var string|null
     *
     * @ORM\Column(name="password_hash", type="string", length=32)
     */
    #[ORM\Column(name: 'password_hash', type: Types::STRING, length: 32)]
    private $passwordHash;

    /**
     * @var string|null
     *
     * @ORM\Column(name="activation_code", type="string", length=12)
     */
    #[ORM\Column(name: 'activation_code', type: Types::STRING, length: 12)]
    private $activationCode;

    public function __construct(string $email, string $password)
    {
        parent::__construct();
        $this
            ->setEmail($email)
            ->setPassword($password);
    }

    public function init(): void
    {
        $this->setActivationCode($this->generateString(12));
    }

    /**
     * Generates a random password
     */
    public function generateString(int $length = 8): string
    {
        $length = $length;
        if ($length < 0) {
            throw new \Exception("Invalid password length '$length'");
        }
        $set = 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $num = strlen($set);
        $ret = '';
        for ($i = 0; $i < $length; ++$i) {
            $ret .= $set[mt_rand(0, $num - 1)];
        }

        return $ret;
    }

    /**
     * Generates a password hash
     */
    public function generatePasswordHash(string $password): string
    {
        return md5($password.self::PASSWORD_SALT);
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        $this->setRoleId($email);

        return $this;
    }

    public function getPasswordHash(): ?string
    {
        return $this->passwordHash;
    }

    public function setPassword(string $password): self
    {
        $this->passwordHash = $this->generatePasswordHash(trim($password));

        return $this;
    }

    public function getActivationCode(): ?string
    {
        return $this->activationCode;
    }

    public function setActivationCode(string $activationCode): self
    {
        $this->activationCode = $activationCode;

        return $this;
    }
}
