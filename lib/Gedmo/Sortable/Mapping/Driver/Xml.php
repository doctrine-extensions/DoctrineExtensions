<?php

namespace Gedmo\Sortable\Mapping\Driver;

use Gedmo\Mapping\Driver\XmlFileDriver;
use Gedmo\Mapping\ExtensionMetadataInterface;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;

/**
 * This is a xml mapping driver for Sortable
 * behavioral extension. Used for extraction of extended
 * metadata from xml specificaly for Sortable
 * extension.
 *
 * @author Lukas Botsch <lukas.botsch@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Xml extends XmlFileDriver
{
    /**
     * {@inheritDoc}
     */
    public function loadExtensionMetadata(ClassMetadata $meta, ExtensionMetadataInterface $exm)
    {
        $xml = $this->getMapping($meta->name);
        if (isset($xml->field)) {
            foreach ($xml->field as $mapping) {
                $field = $this->getAttribute($mapping, 'name');
                $mapping = $mapping->children(self::GEDMO_NAMESPACE_URI);

                if (isset($mapping->sortable)) {
                    $data = $mapping->sortable;
                    $groups = array();
                    if ($this->isAttributeSet($data, 'groups')) {
                        $groups = array_map('trim', explode(',', (string)$this->getAttribute($data, 'groups')));
                    }
                    $exm->map($field, array(
                        'groups' => $groups,
                        'rootClass' => $meta->isMappedSuperclass ? null : $meta->name,
                    ));
                }
            }
        }
    }
}
