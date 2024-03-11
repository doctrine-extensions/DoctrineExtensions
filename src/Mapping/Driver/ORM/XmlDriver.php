<?php

namespace Gedmo\Mapping\Driver\ORM;

use Doctrine\ORM\Mapping\Driver\XmlDriver as BaseXmlDriver;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\Persistence\Mapping\Driver\FileLocator;
use DOMDocument;

class XmlDriver extends BaseXmlDriver
{
    public function __construct(
        string|array|FileLocator $locator,
        string $fileExtension = self::DEFAULT_FILE_EXTENSION,
        private readonly bool $isXsdValidationEnabled = true,
    )
    {
        parent::__construct($locator, $fileExtension, $isXsdValidationEnabled);
    }

    protected function loadMappingFile($file)
    {
        $this->validateMapping($file);

        $result = [];
        // Note: we do not use `simplexml_load_file()` because of https://bugs.php.net/bug.php?id=62577
        $xmlElement = simplexml_load_string(file_get_contents($file));
        assert($xmlElement !== false);

        if (isset($xmlElement->entity)) {
            foreach ($xmlElement->entity as $entityElement) {
                /** @psalm-var class-string $entityName */
                $entityName          = (string) $entityElement['name'];
                $result[$entityName] = $entityElement;
            }
        } elseif (isset($xmlElement->{'mapped-superclass'})) {
            foreach ($xmlElement->{'mapped-superclass'} as $mappedSuperClass) {
                /** @psalm-var class-string $className */
                $className          = (string) $mappedSuperClass['name'];
                $result[$className] = $mappedSuperClass;
            }
        } elseif (isset($xmlElement->embeddable)) {
            foreach ($xmlElement->embeddable as $embeddableElement) {
                /** @psalm-var class-string $embeddableName */
                $embeddableName          = (string) $embeddableElement['name'];
                $result[$embeddableName] = $embeddableElement;
            }
        }

        return $result;
    }

    private function validateMapping(string $file): void
    {
        if (! $this->isXsdValidationEnabled) {
            return;
        }

        $backedUpErrorSetting = libxml_use_internal_errors(true);

        try {
            $document = new DOMDocument();
            $document->load($file);

            if (! $document->schemaValidate(__DIR__ . '/../../../../doctrine-mapping.xsd')) {
                throw MappingException::fromLibXmlErrors(libxml_get_errors());
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($backedUpErrorSetting);
        }
    }
}