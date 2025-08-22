<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Blameable;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Gedmo\AbstractTrackingListener;
use Gedmo\Blameable\Mapping\Event\BlameableAdapter;
use Gedmo\Exception\InvalidArgumentException;
use Gedmo\Tool\ActorProviderInterface;

/**
 * The Blameable listener handles the update of
 * dates on creation and update.
 *
 * @phpstan-extends AbstractTrackingListener<array, BlameableAdapter>
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @final since gedmo/doctrine-extensions 3.11
 */
class BlameableListener extends AbstractTrackingListener
{
    protected ?ActorProviderInterface $actorProvider = null;

    /**
     * @var mixed
     */
    protected $user;

    /**
     * Get the user value to set on a blameable field
     *
     * @param ClassMetadata<object> $meta
     * @param string                $field
     * @param BlameableAdapter      $eventAdapter
     *
     * @return mixed
     */
    public function getFieldValue($meta, $field, $eventAdapter)
    {
        $actor = $this->actorProvider instanceof ActorProviderInterface ? $this->actorProvider->getActor() : $this->user;

        if ($meta->hasAssociation($field)) {
            if (null !== $actor && !is_object($actor)) {
                throw new InvalidArgumentException('Blame is reference, user must be an object');
            }

            return $actor;
        }

        // ok so it's not an association, then it is a string, or an object
        if (is_object($actor)) {
            if (method_exists($actor, 'getUserIdentifier')) {
                return (string) $actor->getUserIdentifier();
            }
            if (method_exists($actor, 'getUsername')) {
                return (string) $actor->getUsername();
            }
            if (method_exists($actor, '__toString')) {
                return $actor->__toString();
            }

            throw new InvalidArgumentException('Field expects string, user must be a string, or object should have method getUserIdentifier, getUsername or __toString');
        }

        return $actor;
    }

    /**
     * Set an actor provider for the user value.
     */
    public function setActorProvider(ActorProviderInterface $actorProvider): void
    {
        $this->actorProvider = $actorProvider;
    }

    /**
     * Set a user value to return.
     *
     * If an actor provider is also provided, it will take precedence over this value.
     *
     * @param mixed $user
     *
     * @return void
     */
    public function setUserValue($user)
    {
        $this->user = $user;
    }

    protected function getNamespace()
    {
        return __NAMESPACE__;
    }
}
