<?php

namespace Gedmo\Loggable\Document;

use Gedmo\Loggable\Log as BaseLog;

/**
 * @Document
 */
class Log extends BaseLog
{
    /**
     * @Id
     */
    protected $id;

    /**
     * @String
     */
    protected $user;

    /**
     * @String
     */
    protected $action;

    /**
     * The return value of __toString
     *
     * @String
     */
    protected $object;

    /**
     * @Date
     */
    protected $date;

    public function actualizeDate()
    {
        $this->date = new \MongoDate();
    }
}