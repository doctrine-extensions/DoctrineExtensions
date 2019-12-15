<?php

namespace Gedmo\Blameable;

/**
 * Interface for Blameable extension's actor
 *
 * @author Jacob Thomason <jacob@rentpost.com>
 */
interface BlameableActorInterface
{

    /**
     * Gets the actor value used for Blameable
     *
     * @return mixed
     */
    public function getActor();
}
