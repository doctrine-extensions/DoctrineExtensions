<?php

namespace Gedmo\Loggable;

/**
 * Base class for Log object
 *
 * @author Boussekeyt Jules <jules.boussekeyt@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Loggable
 * @subpackage Log
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
abstract class AbstractHistoryLog
{
    public function __construct()
    {
        $this->actualizeDate();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setUser($user)
    {
        $this->user = $user;
    }

    public function getAction()
    {
        return $this->action;
    }

    public function setAction($action)
    {
        $this->action = $action;
    }

    public function getObjectClass()
    {
        return $this->objectClass;
    }

    public function setObjectClass($objectClass)
    {
        $this->objectClass = $objectClass;
    }

    public function setForeignKey($foreignKey)
    {
        $this->foreignKey = $foreignKey;
    }

    public function getForeignKey()
    {
        return $this->foreignKey;
    }

    abstract protected function actualizeDate();
}