<?php

namespace Gedmo\Translatable\Document;

/**
 * Gedmo\Translatable\Document\Translation
 *
 * @Document
 * @Indexes(
 * 	@UniqueIndex(name="lookup_unique_idx", keys={
 * 		"locale"="asc",
 * 		"entity"="asc",
 * 		"foreign_key"="asc",
 * 		"field"="asc"
 *  }),
 *  @Index(name="translations_lookup_idx", keys={
 *  	"locale", 
 *  	"entity", 
 *  	"foreign_key"
 *  })
 * )
 */
class Translation extends AbstractTranslation
{
    /**
     * All required columns are mapped through inherited superclass
     */
}