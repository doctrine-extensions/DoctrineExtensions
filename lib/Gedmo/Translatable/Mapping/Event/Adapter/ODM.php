<?php

namespace Gedmo\Translatable\Mapping\Event\Adapter;

use Gedmo\Mapping\Event\Adapter\ODM as BaseAdapterODM;
use Doctrine\ODM\MongoDB\Cursor;
use MongoId;
use Gedmo\Translatable\Mapping\Event\TranslatableAdapterInterface;
use Doctrine\ODM\MongoDB\Proxy\Proxy;

/**
 * Doctrine event adapter for ODM adapted
 * for Translatable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class ODM extends BaseAdapterODM implements TranslatableAdapterInterface
{
    /**
     * {@inheritDoc}
     */
    public function getTranslationCollection($object, $translationClass)
    {
        $dm = $this->getObjectManager();
        $meta = $dm->getClassMetadata(get_class($object));
        $tmeta = $dm->getClassMetadata($translationClass);

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
        $dm = $this->getObjectManager();
        // first look in identityMap, will save one SELECT query
        foreach ($dm->getUnitOfWork()->getIdentityMap() as $className => $objects) {
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
            $meta = $dm->getClassMetadata(get_class($object));
            $qb = $dm
                ->createQueryBuilder($translationClass)
                ->field('locale')->equals($locale)
                ->field('object.$id')->equals(new MongoId($id))
                ->limit(1)
            ;
            $result = $qb->getQuery()->execute();
            if ($result instanceof Cursor) {
                return current($result->toArray());
            }
        }
        return null;
    }
}
