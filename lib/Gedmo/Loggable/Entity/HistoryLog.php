<?php

namespace Gedmo\Loggable\Entity;

use Gedmo\Loggable\AbstractHistoryLog;

/**
 * @Entity
 * @gedmo:Loggable
 */
class HistoryLog extends AbstractHistoryLog
{
    /**
     * @var integer $id
     *
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string $user
     *
     * @Column(name="user", type="string", length=128)
     */
    protected $user;

    /**
     * @var string $action
     *
     * @Column(type="string", length=64)
     */
    protected $action;

    /**
     * @var string $objectClass
     *
     * @Column(name="object_class", type="string", length=255)
     */
    protected $objectClass;

    /**
     * @var string $foreignKey
     *
     * @Column(name="foreign_key", type="string", length=64)
     */
    protected $foreignKey;

    /**
     * @var string $date
     *
     * @Column(name="date", type="datetime", length=8)
     */
    protected $date;

    protected function actualizeDate()
    {
        $this->date = new \DateTime();
    }
}