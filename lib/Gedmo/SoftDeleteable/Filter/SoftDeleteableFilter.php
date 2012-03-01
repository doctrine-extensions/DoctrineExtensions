<?php

namespace Gedmo\SoftDeleteable\Filter;

use Doctrine\ORM\Mapping\ClassMetaData,
    Doctrine\ORM\Query\Filter\SQLFilter,
    Gedmo\SoftDeleteable\SoftDeleteableListener;

/**
 * The SoftDeleteableFilter adds the condition necessary to
 * filter entities which were deleted "softly"
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.SoftDeleteable
 * @subpackage Filter
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

class SoftDeleteableFilter extends SQLFilter
{
    protected $configuration;


    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        $config = $this->getConfiguration($targetEntity);

        if (!isset($config['softDeleteable']) || !$config['softDeleteable']) {
            return '';
        }

        return $targetTableAlias.'.'.$config['fieldName'].' IS NULL';
    }

    protected function getConfiguration(ClassMetadata $meta)
    {
        if ($this->configuration === null) {
            $refl = new \ReflectionProperty('Doctrine\ORM\Query\Filter\SQLFilter', 'em');
            $refl->setAccessible(true);
            $em = $refl->getValue($this);
            $evm = $em->getEventManager();

            foreach ($evm->getListeners() as $listeners) {
                foreach ($listeners as $listener) {
                    if ($listener instanceof SoftDeleteableListener) {
                        $this->configuration = $listener->getConfiguration($em, $meta->name);

                        break;
                    }
                }

                if ($this->configuration === null) {
                    break;
                }
            }

            if ($this->configuration === null) {
                throw new \RuntimeException('Listener "SoftDeleteableListener" was not added to the EventManager!');
            }
        }

        return $this->configuration;
    }
}
