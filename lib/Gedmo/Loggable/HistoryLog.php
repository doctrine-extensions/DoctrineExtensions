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
abstract class HistoryLog
{
    public function __construct()
    {
        $this->actualizeDate();
    }

    public function __toString()
    {
        return sprintf('%s %s %s %s',
            $this->user,
            $this->action,
            $this->object,
            $this->date->format('Y-m-d H:i:s')
        );
    }

    abstract function actualizeDate();

    public function getDate()
    {
        return $this->date;
    }

    public function setDate(\DateTime $date)
    {
        $this->date = $date;
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

    public function getObject()
    {
        return $this->object;
    }

    public function setObject($object)
    {
        $this->object = (string) $object;
    }
}