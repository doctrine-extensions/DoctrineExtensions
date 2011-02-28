<?php

namespace Gedmo\Loggable\Entity;

use Gedmo\Loggable\HistoryLog as BaseHistoryLog;

/**
 * @Entity
 * @gedmo:Loggable
 */
class HistoryLog extends BaseHistoryLog
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
     * @Column(name="user", type="string", length=8)
     */
    protected $user;

    /**
     * @var string $action
     *
     * @Column(name="action", type="string", length=8)
     */
    protected $action;

    /**
     * @var string $object
     *
     * @Column(name="object", type="string", length=8)
     */
    protected $object;

    /**
     * @var string $date
     *
     * @Column(name="date", type="datetime", length=8)
     */
    protected $date;

    public function actualizeDate()
    {
        $this->date = new \DateTime();
    }
}