<?php
namespace Tree\Fixture;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
 * @ORM\Table(name="user_ldap")
 */
class UserLDAP extends User
{
    public function __construct($ldapUserName)
    {
        parent::__construct('next@something.com', 'pass');
    }
}
