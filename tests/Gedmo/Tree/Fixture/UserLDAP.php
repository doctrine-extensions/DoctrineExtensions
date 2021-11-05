<?php

namespace Gedmo\Tests\Tree\Fixture;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
 * @ORM\Table(name="user_ldap")
 */
class UserLDAP extends User
{
    public function __construct(string $ldapUserName = 'next@something.com')
    {
        parent::__construct($ldapUserName, 'pass');
    }
}
