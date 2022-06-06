<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping\Fixture\Yaml;

class User
{
    private $id;

    private $password;

    private $username;

    private $company;

    private $localeField;

    /**
     * Get id
     *
     * @return int $id
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set password
     */
    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    /**
     * Get password
     *
     * @return string $password
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * Set username
     */
    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    /**
     * Get username
     *
     * @return string $username
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * Set company
     */
    public function setCompany(string $company): void
    {
        $this->company = $company;
    }

    /**
     * Get company
     *
     * @return string $company
     */
    public function getCompany(): string
    {
        return $this->company;
    }
}
