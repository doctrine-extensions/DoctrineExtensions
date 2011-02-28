<?php

namespace Gedmo\Loggable\Document;

use Gedmo\Loggable\AbstractHistoryLog;

/**
 * @Document
 */
class HistoryLog extends AbstractHistoryLog
{
    /**
     * @var string $id
     *
     * @Id
     */
    protected $id;

    /**
     * @var string $user
     *
     * @String
     */
    protected $user;

    /**
     * @var string $action
     *
     * @String
     */
    protected $action;

    /**
     * @var string $objectClass
     *
     * @String(name="object_class")
     */
    protected $objectClass;

    /**
     * @var string $foreignKey
     *
     * @String(name="foreign_key")
     */
    protected $foreignKey;

    /**
     * @var MongoData $date 
     *
     * @Date
     */
    protected $date;

    protected function actualizeDate()
    {
        $this->date = new \MongoDate();
    }
}