<?php

namespace Gedmo\SoftDeleteable\Filter\ODM;

use Doctrine\ODM\MongoDB\Query\Filter\BsonFilter;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Gedmo\SoftDeleteable\SoftDeleteableListener;
use Gedmo\Exception\RuntimeException;

class SoftDeleteableFilter extends BsonFilter
{
    protected $listener;
    protected $documentManager;
    protected $disabled = array();

    /**
     * Gets the criteria part to add to a query.
     *
     * @return array The criteria array, if there is available, empty array otherwise
     */
    public function addFilterCriteria(ClassMetadata $meta)
    {
        $class = $meta->getName();
        if (array_key_exists($class, $this->disabled) && $this->disabled[$class] === true) {
            return array();
        } elseif (array_key_exists($meta->rootDocumentName, $this->disabled) && $this->disabled[$meta->rootDocumentName] === true) {
            return array();
        }

        $exm = $this->getListener()->getConfiguration($this->getDocumentManager(), $meta->name);
        if (!$exm || $exm->isEmpty()) {
            return array();
        }
        $column = $meta->fieldMappings[$exm->getField()];
        if ($exm->timeAware()) {
            //@fixme timeAware is not yet respected here!
            throw new RuntimeException("Softdeleteable timeAware is not supported in mongodb odm yet");
            return array(
                $column['fieldName'] => NULL
            );
        } else {
            return array(
                $column['fieldName'] => NULL
            );
        }
    }

    protected function getListener()
    {
        if ($this->listener === null) {
            foreach ($this->getDocumentManager()->getEventManager()->getListeners() as $listeners) {
                foreach ($listeners as $listener) {
                    if ($listener instanceof SoftDeleteableListener) {
                        $this->listener = $listener;
                        break 2;
                    }
                }
            }

            if ($this->listener === null) {
                throw new \RuntimeException('Listener "SoftDeleteableListener" was not added to the EventManager!');
            }
        }
        return $this->listener;
    }

    protected function getDocumentManager()
    {
        if ($this->documentManager === null) {
            $refl = new \ReflectionProperty('Doctrine\ODM\MongoDB\Query\Filter\BsonFilter', 'dm');
            $refl->setAccessible(true);
            $this->documentManager = $refl->getValue($this);
        }

        return $this->documentManager;
    }

    public function disableForDocument($class)
    {
        $this->disabled[$class] = true;
    }

    public function enableForDocument($class)
    {
        $this->disabled[$class] = false;
    }

}
