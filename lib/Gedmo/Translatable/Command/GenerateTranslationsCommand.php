<?php

namespace Gedmo\Translatable\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Common\Persistence\ObjectManager;

use Doctrine\ODM\MongoDB\DocumentManager;

use Gedmo\Mapping\Driver;
use Gedmo\Mapping\Driver\Chain as DriverChain;
use Gedmo\Mapping\ExtensionMetadataFactory;
use Gedmo\Translatable\TranslatableListener;
use Gedmo\Translatable\Mapping\Driver\Annotation as TranslatableAnnotationDriver;
use Gedmo\Exception\InvalidMappingException;

class GenerateTranslationsCommand extends Command
{
    const TRANSLATION_ANNOTATED_ORM_TPL = <<<EOT
<?php

namespace %ns%;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Translatable\%type%\MappedSuperclass\AbstractTranslation;

/**
 * @ORM\Entity
 * @ORM\Table(uniqueConstraints={
 *   @ORM\UniqueConstraint(name="%index%", columns={"locale", "object_id"})
 * })
 */
class %targetClass%Translation extends AbstractTranslation
{
    /**
     * @ORM\ManyToOne(targetEntity="%targetClass%", inversedBy="translations")
     * @ORM\JoinColumn(name="object_id", referencedColumnName="%rel_id%", onDelete="CASCADE", nullable=false)
     */
    protected \$object;
}
EOT;

const TRANSLATION_ANNOTATED_ODM_TPL = <<<EOT
<?php

namespace %ns%;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoODM;
use Gedmo\Translatable\%type%\MappedSuperclass\AbstractTranslation;

/**
 * @MongoODM\Document
 */
class %targetClass%Translation extends AbstractTranslation
{
    /**
     * @MongoODM\ReferenceOne(targetDocument="%targetClass%", inversedBy="translations")
     */
    protected \$object;
}
EOT;

const TRANSLATION_TPL = <<<EOT
<?php

namespace %ns%;

use Gedmo\Translatable\%type%\MappedSuperclass\AbstractTranslation;

class %targetClass%Translation extends AbstractTranslation
{
    /**
     * Relation to translated %type% overides mapping
     *
     * @var \\%ns%\\%targetClass%
     */
    protected \$object;
}
EOT;
    protected $em;
    protected $output;
    protected $annotate = true;

    protected $typeAlias = array(
        Type::DATETIMETZ    => '\DateTime',
        Type::DATETIME      => '\DateTime',
        Type::DATE          => '\DateTime',
        Type::TIME          => '\DateTime',
        Type::OBJECT        => '\stdClass',
        Type::BIGINT        => 'integer',
        Type::SMALLINT      => 'integer',
        Type::TEXT          => 'string',
        Type::BLOB          => 'string',
        Type::DECIMAL       => 'float',
        Type::JSON_ARRAY    => 'array',
        Type::SIMPLE_ARRAY  => 'array',
    );

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('gedmo:translatable:generate')
            ->setDescription('Generates or updates translations for translatable entities.')
            ->setDefinition(array(
            ))
            ->setHelp(<<<EOT
Executes arbitrary DQL directly from the command line.
EOT
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        if ($this->getHelperSet()->has('em')) {
            $this->generate($this->getHelper('em')->getEntityManager());
        }
        if ($this->getHelperSet()->has('dm')) {
            $this->generate($this->getHelper('dm')->getDocumentManager());
        }
    }

    protected function generate(ObjectManager $om)
    {
        $emf = new ExtensionMetadataFactory($om, 'Gedmo\Translatable', $this->getAnnotationReader());
        $this->disableTranslatableListener($om);
        $metadatas = $om->getMetadataFactory()->getAllMetadata();
        foreach ($metadatas as $meta) {
            try {
                $config = $emf->getExtensionMetadata($meta);
            } catch (InvalidMappingException $e) {
                $config = $e->currentConfig;
            }
            if ($config && ($driver = $this->getExtensionDriverUsed($emf, $meta))) {
                $driverType = end($parts = explode('\\', get_class($driver)));
                $this->output->writeLn("Found Translatable mapping for <info>{$meta->name}</info> checking required updates for <comment>{$driverType}</comment> driver");
                switch ($driverType) {
                    case "Annotation":
                        $this->generateTranslationClass($om, $driver, $config, $meta);
                        break;
                    case "Xml":
                        $this->generateXmlTranslationMapping($om, $driver, $config, $meta);
                        $this->generateTranslationClass($om, $driver, $config, $meta);
                        break;
                    case "Yaml":
                        $this->generateYamlTranslationMapping($om, $driver, $config, $meta);
                        $this->generateTranslationClass($om, $driver, $config, $meta);
                        break;
                }
                $this->output->writeLn("");
            }
        }
    }

    private function generateXmlTranslationMapping(ObjectManager $om, Driver $driver, array $config, $meta)
    {
        $type = $om instanceof EntityManager ? 'entity' : 'document';
        $refl = new \ReflectionProperty(get_class($driver), 'locator');
        $refl->setAccessible(true);
        $locator = $refl->getValue($driver);

        if (!$file = $locator->findMappingFile($meta->name)) {
            throw new \RuntimeException("Could not locate yaml mapping file");
        }

        // find extension
        $refl = new \ReflectionProperty(get_class($driver), '_extension');
        $refl->setAccessible(true);
        $extension = $refl->getValue($driver);

        $xml = simplexml_load_file($file);
        $root = null;
        foreach ($xml->{$type} as $domain) {
            if (($name = (string)$domain['name']) === $meta->name) {
                $root = $domain;
                break;
            }
        }
        $inversedRelation = false;
        $sname = $meta->getReflectionClass()->getShortName() . 'Translation';
        $relName = $type === 'entity' ? 'one-to-many' : 'reference-many';
        if (isset($root->{$relName})) {
            foreach ($root->{$relName} as $assoc) {
                $attrs = $assoc->attributes();
                if (strrpos((string)$attrs['target-'.$type], $sname) === 0) {
                    $inversedRelation = (string)$attrs['field'];
                    break;
                }
            }
        }
        // add mapping for translations collection
        if (!$inversedRelation) {
            $this->output->writeLn("Adding iversed relation mapping for <info>{$meta->name}</info> as <comment>translations</comment> collection");
            // no relation to translations found, add it
            $transAssoc = $root->addChild($relName);
            $transAssoc->addAttribute('field', 'translations');
            $transAssoc->addAttribute('target-'.$type, $sname);
            $transAssoc->addAttribute('mapped-by', 'object');
            // cascade
            $cascadeXml = $transAssoc->addChild('cascade');
            $cascadeXml->addChild('cascade-persist');
            $cascadeXml->addChild('cascade-remove');
        }
        // now generate a translation mapping
        $translationFile = dirname($file) . DIRECTORY_SEPARATOR . basename($file, $extension) . 'Translation' . $extension;
        $changed = false;
        $fname = $meta->name . 'Translation';
        if (!file_exists($translationFile)) {
            $mapperName = $type === 'entity' ? 'doctrine-mapping' : 'doctrine-mongo-mapping';
            $transXml = new \SimpleXmlElement("<?xml version=\"1.0\" encoding=\"utf-8\"?><{$mapperName} ".
                "xmlns=\"http://doctrine-project.org/schemas/orm/{$mapperName}\" " .
                "xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" ".
                "xsi:schemaLocation=\"http://doctrine-project.org/schemas/orm/{$mapperName} http://doctrine-project.org/schemas/orm/{$mapperName}.xsd\" />");

            $ent = $transXml->addChild($type);
            $ent->addAttribute('name', $fname);

            $assoc = $ent->addChild($relName);
            $assoc->addAttribute('field', 'object');
            $assoc->addAttribute('target-'.$type, $meta->getReflectionClass()->getShortName());
            $assoc->addAttribute('inversed-by', $inversedRelation ?: 'translations');

            if ($type === 'entity') {
                // unique index
                $cs = $ent->addChild('unique-constraints')->addChild('unique-constraint');
                $cs->addAttribute('name', 'UNIQ_' . substr(strtoupper(md5($fname)), 0, 16));
                $cs->addAttribute('columns', 'locale, object_id');

                $joinColumnXml = $assoc->addChild('join-column');
                $joinColumnXml->addAttribute('name', 'object_id');
                $joinColumnXml->addAttribute('referenced-column-name', $meta->getSingleIdentifierFieldName());
                $joinColumnXml->addAttribute('on-delete', 'CASCADE');
            }
            $changed = true;
        } else {
            $transXml = simplexml_load_file($translationFile);
        }
        // update field mappings
        foreach ($config['fields'] as $field => $options) {
            // first check if we have this field
            $had = false;
            if (isset($transXml->{$type}->field)) {
                foreach ($transXml->{$type}->field as $fieldNode) {
                    $attrs = $fieldNode->attributes();
                    if ($field === (string)$attrs['name']) {
                        $had = true;
                        foreach ($root->field as $targetNode) {
                            $attrsTarget = $targetNode->attributes();
                            if ($field === (string)$attrsTarget['name']) {
                                foreach ($attrsTarget as $key => $val) {
                                    if (!isset($attrs[$key]) || (string)$attrs[$key] !== (string)$val) {
                                        $fieldNode->attributes()->{$key} = (string)$val;
                                        $this->output->writeLn("Updating mapping for <info>{$fname}</info> - translatable property <comment>{$field}</comment> attribute <comment>{$key}</comment>");
                                        $changed = true;
                                    }
                                }
                                break;
                            }
                        }
                        continue;
                    }
                }
            }
            if ($had) {
                continue; // do not need to add it
            }
            foreach ($root->field as $fieldNode) {
                $attrs = $fieldNode->attributes();
                if ((string)$attrs['name'] === $field) {
                    if (isset($attrs['unique']) && "true" === (string)$attrs['unique']) {
                        throw new \LogicException("Translatable target: {$meta->name} cannot have unique translatable field"
                            . ", place unique on {$fname} field '{$field}' mapping instead.");
                    }
                    $fieldXml = $transXml->{$type}->addChild('field');
                    foreach ($attrs as $key => $val) {
                        $fieldXml->addAttribute($key, (string)$val);
                    }
                    $this->output->writeLn("Adding translatable property <comment>{$field}</comment> mapping for <info>{$fname}</info>");
                    $changed = true;
                }
            }
        }
        // save modifications
        if ($changed && false === $this->saveXml($translationFile, $transXml, $fname)) {
            throw new \RuntimeException("Could not write file: $translationFile");
        }
        if (!$inversedRelation && false === $this->saveXml($file, $xml, $meta->name)) {
            throw new \RuntimeException("Could not write file: $file");
        }
    }

    private function saveXml($filename, $xml, $name)
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->loadXML($xml->asXML());
        $dom->formatOutput = true;
        $this->output->writeLn("Saving xml mapping for <info>{$name}</info> into file <comment>{$filename}</comment>");
        return file_put_contents($filename, $dom->saveXML());
    }

    private function generateYamlTranslationMapping(ObjectManager $om, Driver $driver, array $config, $meta)
    {
        $type = $om instanceof EntityManager ? 'Entity' : 'Document';
        $refl = new \ReflectionProperty(get_class($driver), 'locator');
        $refl->setAccessible(true);
        $locator = $refl->getValue($driver);

        if (!$file = $locator->findMappingFile($meta->name)) {
            throw new \RuntimeException("Could not locate yaml mapping file");
        }

        // find extension
        $refl = new \ReflectionProperty(get_class($driver), '_extension');
        $refl->setAccessible(true);
        $extension = $refl->getValue($driver);

        $yaml = Yaml::parse($file);
        $relName = $type === 'Entity' ? 'oneToMany' : 'fields';
        $sname = $meta->getReflectionClass()->getShortName() . 'Translation';
        $inversedRelation = false;
        if (isset($yaml[$meta->name][$relName])) {
            foreach ($yaml[$meta->name][$relName] as $assocFieldName => $assoc) {
                if (isset($assoc['target'.$type]) && strrpos($assoc['target'.$type], $sname) === 0) {
                    $inversedRelation = $assocFieldName;
                    break;
                }
            }
        }
        if (!$inversedRelation) {
            // add mapping for translations collection
            $this->output->writeLn("Adding iversed relation mapping for <info>{$meta->name}</info> as <comment>translations</comment> collection");
            if (!isset($yaml[$meta->name][$relName])) {
                $yaml[$meta->name][$relName] = array();
            }
            $yaml[$meta->name][$relName]['translations'] = array(
                'target'.$type => $sname,
                'mappedBy' => 'object',
                'cascade' => array('persist', 'remove')
            );
            if ($type === 'Document') {
                $yaml[$meta->name][$relName]['translations']['type'] = 'many';
            }
        }

        // now generate a translation mapping
        $translationFile = dirname($file) . DIRECTORY_SEPARATOR . basename($file, $extension) . 'Translation' . $extension;
        $changed = false;
        $fname = $meta->name . 'Translation';
        $relName = $type === 'Entity' ? 'manyToOne' : 'referenceOne';

        if (!file_exists($translationFile)) {
            $translationYaml = array($fname => array(
                $relName => array(
                    'object' => array(
                        'target'.$type => $sname,
                        'inversedBy' => $inversedRelation ?: 'translations'
                    )
                )
            ));
            if ($type === 'Entity') {
                $translationYaml[$fname]['type'] = 'entity';
                $translationYaml[$fname][$relName]['object']['joinColumn'] = array(
                    'name' => 'object_id',
                    'referencedColumnName' => $meta->getSingleIdentifierFieldName(),
                    'onDelete' => 'CASCADE',
                );
                $translationYaml[$fname]['fields'] = array();
            }
            $changed = true;
        } else {
            $translationYaml = Yaml::parse($translationFile);
        }
        // update field mappings
        foreach ($config['fields'] as $field => $options) {
            if (!isset($translationYaml[$fname]['fields'][$field])) {
                $fieldMapping = $yaml[$meta->name]['fields'][$field];
                unset($fieldMapping['gedmo']);
                if (isset($fieldMapping['unique'])) {
                    throw new \LogicException("Translatable entity: {$meta->name} cannot have unique translatable field"
                        . ", place unique on {$fname} field '{$field}' mapping instead.");
                }
                $translationYaml[$fname]['fields'][$field] = $fieldMapping;
                $this->output->writeLn("Adding translatable property <comment>{$field}</comment> mapping for <info>{$fname}</info>");
                $changed = true;
            } else {
                // field is available, but check attributes
                $origMapping = $yaml[$meta->name]['fields'][$field];
                $tranMapping = $translationYaml[$fname]['fields'][$field];
                foreach ($origMapping as $key => $val) {
                    if ($key !== 'gedmo' && (!isset($tranMapping[$key]) || $tranMapping[$key] !== $val)) {
                        $translationYaml[$fname]['fields'][$field][$key] = $val;
                        $this->output->writeLn("Updating mapping for <info>{$fname}</info> - translatable property <comment>{$field}</comment> attribute <comment>{$key}</comment>");
                        $changed = true;
                    }
                }
            }
        }
        // save modifications
        if ($changed && false === $this->saveYaml($translationFile, $translationYaml, $fname)) {
            throw new \RuntimeException("Could not write file: $translationFile");
        }
        if (!$inversedRelation && false === $this->saveYaml($file, $yaml, $meta->name)) {
            throw new \RuntimeException("Could not write file: $file");
        }
    }

    private function saveYaml($filename, $yaml, $name)
    {
        $this->output->writeLn("Saving yaml mapping for <info>{$name}</info> into file <comment>{$filename}</comment>");
        return file_put_contents($filename, Yaml::dump($yaml, 120));
    }

    private function generateTranslationClass(ObjectManager $om, Driver $driver, array $config, $meta)
    {
        $annotate = $this->annotate || $driver instanceof TranslatableAnnotationDriver;
        if ($om instanceof EntityManager) {
            $type = 'Entity';
            $tpl = $annotate ? self::TRANSLATION_ANNOTATED_ORM_TPL : self::TRANSLATION_TPL;
        } elseif ($om instanceof DocumentManager) {
            $type = 'Document';
            $tpl = $annotate ? self::TRANSLATION_ANNOTATED_ODM_TPL : self::TRANSLATION_TPL;
        } else {
            throw new \RuntimeException("Unsupported object manager: ".get_class($om));
        }

        $refl = $meta->getReflectionClass();
        $sname = $refl->getShortName() . 'Translation';
        $tname = $config['translationClass'];
        $transFileName = dirname($refl->getFileName()) . DIRECTORY_SEPARATOR . $sname . '.php';

        if (!class_exists($config['translationClass'])) {
            $replace = array(
                '%ns%' => $refl->getNamespaceName(),
                '%type%' => $type,
                '%rel_id%' => $type === 'Entity' ? $meta->getSingleIdentifierFieldName() : $meta->identifier,
                '%targetClass%' => $refl->getShortName(),
                '%index%' => 'UNIQ_' . substr(strtoupper(md5($meta->name)), 0, 16),
            );
            $this->output->writeLn("Creating new translation class <info>{$tname}</info>");
            if (!file_put_contents($transFileName, str_replace(array_keys($replace), array_values($replace), $tpl))) {
                throw new \RuntimeException("Could not write file: $transFileName");
            }
            require $transFileName;
        }

        $refl = new \ReflectionClass($tname);
        $methodBlock = $fieldBlock = '';
        foreach ($config['fields'] as $field => $options) {
            if (!$refl->hasProperty($field)) {
                $this->output->writeLn("Adding translatable property <comment>{$field}</comment> to translation class file <info>{$tname}</info>");

                $ufield = ucfirst($field);
                $mapping = $meta->getFieldMapping($field);
                $colType = $this->getType($mapping['type']);
                $methodBlock .= strlen($methodBlock) ? "\n\n" : "";
                $methodBlock .= <<<EOT
    /**
     * Set translation \${$field} field
     *
     * @param {$colType} \${$field}
     * @return \\$tname
     */
    public function set{$ufield}(\${$field})
    {
        \$this->{$field} = \${$field};
        return \$this;
    }

    /**
     * Get \${$field} translation
     *
     * @return {$colType}
     */
    public function get{$ufield}()
    {
        return \$this->{$field};
    }
EOT;
                if ($annotate) {
                    $callMethod = 'get'.$type.'Column';
                    $column = $this->$callMethod($mapping, $meta);
                } else {
                    $column = '@var '.$colType;
                }

                $fieldBlock .= strlen($fieldBlock) ? "\n\n" : "";
                $fieldBlock .= <<<EOT
    /**
     * Translation value of {$field}
     *
     * {$column}
     */
    private \${$field};
EOT;
            }
        }

        if ($methodBlock && $fieldBlock) {
            $this->injectTranslationCode($fieldBlock, $methodBlock, $refl);
        }
        if (($refl = $meta->getReflectionClass()) && !$refl->hasProperty('translations')) {
            $this->output->writeLn("Adding iversed relation mapping for <info>{$meta->name}</info> as <comment>translations</comment> collection");
            $this->injectInversedRelation($sname, $refl, $annotate, $type);
        }
    }

    private function injectTranslationCode($fields, $methods, $refl)
    {
        $lines = explode("\n", file_get_contents($refl->getFileName()));
        foreach ($lines as $num => $line) {
            if (preg_match('/^protected\s+\$object\s*;$/i', trim($line))) {
                $lines[$num] .= "\n\n" . $fields;
                break;
            }
        }
        for ($i = count($lines) - 1; $i !== 0; $i--) {
            if (trim($lines[$i]) === '}') {
                $lines[$i - 1] .= "\n\n".$methods;
                break;
            }
        }
        if (false === file_put_contents($refl->getFileName(), implode("\n", $lines))) {
            throw new \RuntimeException("Could not write file: $transFileName, check permissions");
        }
    }

    private function injectInversedRelation($targetName, $refl, $annotate, $type)
    {
        $lastProp = array_pop(($props = $refl->getProperties()));
        $visibility = 'public';
        if ($lastProp->isPrivate()) {
            $visibility = 'private';
        } elseif ($lastProp->isProtected()) {
            $visibility = 'protected';
        }

        $annotation = '';
        if ($annotate) {
            if ($type === 'Entity') {
                $annotation = '@ORM\OneToMany(targetEntity="'.$targetName.'", mappedBy="object", cascade={"persist", "remove"})';
            } else {
                $annotation = '@MongoODM\ReferenceMany(targetDocument="'.$targetName.'", mappedBy="object", cascade={"persist", "remove"})';
            }
        }

        $fullTargetName = $refl->getNamespaceName().'\\'.$targetName;
        $name = $refl->getName();
        $inverseRelationBlock = <<<EOT
    /**
     * Translation collection
     *
     * @var \Doctrine\Common\Collections\ArrayCollection
     * $annotation
     */
    private \$translations;
EOT;

        $methodBlock = <<<EOT
    /**
     * Get translations
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getTranslations()
    {
        return \$this->translations;
    }

    /**
     * Add translation
     *
     * @param \\$fullTargetName \$translation
     * @return \\$name
     */
    public function addTranslation($targetName \$translation)
    {
        if (!\$this->translations->contains(\$translation)) {
            \$this->translations->add(\$translation);
            \$translation->setObject(\$this);
        }
        return \$this;
    }

    /**
     * Remove translation
     *
     * @param \\$fullTargetName \$translation
     * @return \\$name
     */
    public function removeTranslation($targetName \$translation)
    {
        if (\$this->translations->contains(\$translation)) {
            \$this->translations->removeElement(\$translation);
        }
        return \$this;
    }
EOT;
        $lines = explode("\n", file_get_contents($refl->getFileName()));
        foreach ($lines as $num => $line) {
            if (preg_match("/^{$visibility}\s+\\\$".$lastProp->getName()."\s*;$/i", trim($line))) {
                $lines[$num] .= "\n\n" . $inverseRelationBlock;
                break;
            }
        }
        for ($i = count($lines) - 1; $i !== 0; $i--) {
            if (trim($lines[$i]) === '}') {
                $lines[$i - 1] .= "\n\n" . rtrim($methodBlock);
                break;
            }
        }
        if (false === file_put_contents($refl->getFileName(), implode("\n", $lines))) {
            throw new \RuntimeException("Could not write file: ".$refl->getFileName().", check permissions");
        }
    }

    private function getDocumentColumn(array $mapping, $meta)
    {
        $field = array();
        if (isset($mapping['type'])) {
            $field[] = 'type="' . $mapping['type'] . '"';
        }
        if (isset($mapping['nullable']) && $mapping['nullable'] === true) {
            $field[] = 'nullable=' . var_export($mapping['nullable'], true);
        }
        if (isset($mapping['options'])) {
            $options = array();
            foreach ($mapping['options'] as $key => $value) {
                $options[] = '"' . $key . '" = "' . $value . '"';
            }
            $field[] = "options={" . implode(', ', $options) . "}";
        }
        return '@MongoODM\\Field(' . implode(', ', $field) . ')';
    }

    private function getEntityColumn(array $mapping, $meta)
    {
        $column = array();
        if (isset($mapping['type'])) {
            $column[] = 'type="' . $mapping['type'] . '"';
        }
        if (isset($mapping['length']) && $mapping['length']) {
            $column[] = 'length=' . $mapping['length'];
        }
        if (isset($mapping['precision']) && $mapping['precision']) {
            $column[] = 'precision=' .  $mapping['precision'];
        }
        if (isset($mapping['scale']) && $mapping['scale']) {
            $column[] = 'scale=' . $mapping['scale'];
        }
        if (isset($mapping['nullable']) && false !== $mapping['nullable']) {
            $column[] = 'nullable=' .  var_export($mapping['nullable'], true);
        }
        if (isset($mapping['columnDefinition'])) {
            $column[] = 'columnDefinition="' . $mapping['columnDefinition'] . '"';
        }
        if (isset($mapping['unique']) && $mapping['unique']) {
            throw new \LogicException("Translatable entity: {$meta->name} cannot have unique translatable field"
                . ", place unique on {$tname} field '{$field}' mapping instead.");
        }

        return '@ORM\Column(' . implode(', ', $column) . ')';
    }

    protected function getType($type)
    {
        return isset($this->typeAlias[$type]) ? $this->typeAlias[$type] : $type;
    }

    private function getExtensionDriverUsed(ExtensionMetadataFactory $emf, $meta)
    {
        $refl = new \ReflectionProperty('Gedmo\Mapping\ExtensionMetadataFactory', 'driver');
        $refl->setAccessible('true');
        $driver = $refl->getValue($emf);

        $findDriver = function(DriverChain $driver) use ($meta, &$findDriver) {
            foreach ($driver->getDrivers() as $ns => $nested) {
                if (strpos($meta->name, $ns) === 0) {
                    if ($nested instanceof DriverChain && null !== ($deep = $findDriver($nested))) {
                        return $deep;
                    } else {
                        return $nested;
                    }
                }
            }
            return null;
        };
        return $driver instanceOf DriverChain ? $findDriver($driver) : $driver;
    }

    private function getAnnotationReader()
    {
        $refl = new \ReflectionMethod('Gedmo\Mapping\MappedEventSubscriber', 'getDefaultAnnotationReader');
        $refl->setAccessible('true');
        return $refl->invoke(new TranslatableListener);
    }

    private function disableTranslatableListener(ObjectManager $om)
    {
        $translatable = null;
        foreach ($om->getEventManager()->getListeners() as $listeners) {
            foreach ($listeners as $listener) {
                if ($listener instanceof TranslatableListener) {
                    $translatable = $listener;
                    break;
                }
            }
        }
        if ($translatable) {
            $om->getEventManager()->removeEventListener(array('loadClassMetadata'), $translatable);
        }
    }
}
