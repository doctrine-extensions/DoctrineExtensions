<?php

namespace DoctrineExtensions\Translatable\Repository;

use Doctrine\ORM\EntityRepository,
    Doctrine\ORM\Query;

/**
 * The TranslationRepository has some useful functions
 * to interact with translations.
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package DoctrineExtensions.Translatable.Repository
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TranslationRepository extends EntityRepository
{
    public function findTranslation($foreignKey, $field, $locale, $entity)
    {
    	$qb = $this->createQueryBuilder('trans');
    	$qb->where(
    	    'trans.foreignKey = :foreignKey',
            'trans.locale = :locale',
            'trans.field = :field',
            'trans.entity = :entity'
    	);
    	$q = $qb->getQuery();
    	$result = $q->execute(
            compact('field', 'locale', 'foreignKey', 'entity'),
            Query::HYDRATE_OBJECT
    	);
    	if ($result && is_array($result) && count($result)) {
    		return array_shift($result);
    	}
    	return null;
    }
    
    public function findFieldTranslation($foreignKey, $field, $locale, $entity)
    {
    	$qb = $this->createQueryBuilder('trans');
        $qb->select('trans.content')
            ->where(
            'trans.foreignKey = :foreignKey',
            'trans.locale = :locale',
            'trans.field = :field',
            'trans.entity = :entity'
        );

        $q = $qb->getQuery();
        $result = $q->execute(
            compact('field', 'locale', 'foreignKey', 'entity'),
            Query::HYDRATE_ARRAY
        );
        if ($result && is_array($result) && count($result)) {
            $result = array_shift($result);
            return $result['content'];
        }
        return null;
    }
}