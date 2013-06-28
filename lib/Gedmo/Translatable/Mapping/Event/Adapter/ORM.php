<?php

namespace Gedmo\Translatable\Mapping\Event\Adapter;

use Gedmo\Mapping\Event\Adapter\ORM as BaseAdapterORM;
use Gedmo\Translatable\Mapping\Event\TranslatableAdapterInterface;
use Doctrine\ORM\Proxy\Proxy;

/**
 * Doctrine event adapter for ORM adapted
 * for Translatable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class ORM extends BaseAdapterORM implements TranslatableAdapterInterface
{
    /**
     * {@inheritDoc}
     */
    public function getTranslationCollection($object, $translationClass)
    {
        $em = $this->getObjectManager();
        $meta = $em->getClassMetadata(get_class($object));
        $tmeta = $em->getClassMetadata($translationClass);

        if ($inversed = $tmeta->associationMappings['object']['inversedBy']) {
            return $meta->getReflectionProperty($inversed)->getValue($object);
        }
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function findTranslation($object, $locale, $translationClass)
    {
        $em = $this->getObjectManager();
        // first look in identityMap, will save one SELECT query
        foreach ($em->getUnitOfWork()->getIdentityMap() as $className => $objects) {
            if ($className === $translationClass) {
                foreach ($objects as $trans) {
                    $found = !$trans instanceof Proxy && $trans->getLocale() === $locale && $trans->getObject() === $object;
                    if ($found) {
                        return $trans;
                    }
                }
            }
        }
        // make query only if object has identifier
        if ($id = $this->getIdentifier($object)) {
            $q = $em->createQueryBuilder()
                ->select('trans')
                ->from($translationClass, 'trans')
                ->where('trans.locale = :locale', 'trans.object = :object')
                ->setParameters(compact('locale', 'object'))
                ->getQuery();

            $q->setMaxResults(1);
            if ($result = $q->getResult()) {
                return array_shift($result);
            }
        }
        return null;
    }
}
