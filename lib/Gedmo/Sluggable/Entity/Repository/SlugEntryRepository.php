<?php

namespace Gedmo\Sluggable\Entity\Repository;

use Gedmo\Tool\Wrapper\EntityWrapper,
    Doctrine\ORM\EntityRepository,
    Gedmo\Sluggable\SluggableListener;

/**
 * The SlugEntryRepository has some useful functions
 * to interact with slug history.
 *
 * @author Martin Jantosovic <jantosovic.martin@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class SlugEntryRepository extends EntityRepository
{
    /**
     * Currently used loggable listener
     *
     * @var SluggableListener
     */
    private $listener;

    /**
     * Allow findOneBy*, where * is slugField
     */
    public function __call($method, $arguments)
    {
        switch (true) {
            case (0 === strpos($method, 'findOneBy')):
                $by = substr($method, 9);
                $method = 'findOneBy';
                break;

            default:
                throw new \BadMethodCallException(
                    "Undefined method '$method'. The method name must start with ".
                    "either findBy or findOneBy!"
                );
        }

        if (empty($arguments)) {
            throw ORMException::findByRequiresParameter($method . $by);
        }

        $slugField = lcfirst(\Doctrine\Common\Util\Inflector::classify($by));
        $slugValue = $arguments[0];
        if (count($arguments) > 1) {
            $slugEntry = $this->$method(array(
                'slugField' => $slugField,
                'slugValue' => $slugValue,
                'objectClass' => $arguments[1]
            ));
        } else {
            $slugEntry = $this->$method(array(
                'slugField' => $slugField,
                'slugValue' => $slugValue
            ));
        }

        if ($slugEntry) {
            return $this->getEntityManager()->getRepository($slugEntry->getObjectClass())
                ->find($slugEntry->getObjectId());
        } else
            return NULL;
    }

    /**
     * Get the currently used SluggableListener
     *
     * @throws \Gedmo\Exception\RuntimeException - if listener is not found
     * @return SluggableListener
     */
    private function getSluggableListener()
    {
        if (is_null($this->listener)) {
            foreach ($this->_em->getEventManager()->getListeners() as $event => $listeners) {
                foreach ($listeners as $hash => $listener) {
                    if ($listener instanceof SluggableListener) {
                        $this->listener = $listener;
                        break;
                    }
                }
                if ($this->listener) {
                    break;
                }
            }

            if (is_null($this->listener)) {
                throw new \Gedmo\Exception\RuntimeException('The sluggable listener could not be found');
            }
        }
        return $this->listener;
    }
}
