<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Sortable\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Persistence\NotifyPropertyChanged;
use Doctrine\Persistence\PropertyChangedListener;
use Gedmo\Sortable\Entity\Repository\SortableRepository;

/**
 * @author Charles J. C. Elling, 2017-07-31
 *
 * @ORM\Entity(repositoryClass="Gedmo\Sortable\Entity\Repository\SortableRepository")
 * @ORM\ChangeTrackingPolicy("NOTIFY")
 */
#[ORM\Entity(repositoryClass: SortableRepository::class)]
#[ORM\ChangeTrackingPolicy(value: 'NOTIFY')]
class NotifyNode extends AbstractNode implements NotifyPropertyChanged
{
    /**
     * Listeners that want to be notified about property changes.
     *
     * @var PropertyChangedListener[]
     */
    private $_propertyChangedListeners = [];

    /**
     * Adds a listener that wants to be notified about property changes.
     *
     * @see \Doctrine\Common\NotifyPropertyChanged::addPropertyChangedListener()
     *
     * @return void
     */
    public function addPropertyChangedListener(PropertyChangedListener $listener)
    {
        $this->_propertyChangedListeners[] = $listener;
    }

    public function setName(?string $name): void
    {
        $this->setProperty('name', $name);
    }

    public function setPath(?string $path): void
    {
        $this->setProperty('path', $path);
    }

    public function setPosition(?int $position): void
    {
        $this->setProperty('position', $position);
    }

    /**
     * Notify property change event to listeners
     *
     * @param mixed $oldValue
     * @param mixed $newValue
     */
    protected function triggerPropertyChanged(string $propName, $oldValue, $newValue): void
    {
        foreach ($this->_propertyChangedListeners as $listener) {
            $listener->propertyChanged($this, $propName, $oldValue, $newValue);
        }
    }

    /**
     * @param mixed $newValue
     */
    protected function setProperty(string $property, $newValue): void
    {
        $oldValue = $this->{$property};
        if ($oldValue !== $newValue) {
            $this->triggerPropertyChanged($property, $oldValue, $newValue);
            $this->{$property} = $newValue;
        }
    }
}
