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
     * @ORM\JoinColumn(name="object_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
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
            }
        }
    }

    private function generateXmlTranslationMapping(ObjectManager $om, Driver $driver, array $config, $meta)
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

        $xml = simplexml_load_file($file);
        $ref = null;
        foreach ($xml->entity as $entity) {
            if (($name = (string)$entity['name']) === $meta->name) {
                $ref = &$entity; break;
            }
        }
        die(var_dump($ref));
        $hasInversedRelation = false;
        foreach ($ref->field as $field) {
            //if ($field)
        }
        // add mapping for translations collection
        if ($type === 'Entity') {
            if (!isset($yaml[$meta->name]['oneToMany'])) {
                $yaml[$meta->name]['oneToMany'] = array();
            }
            $yaml[$meta->name]['oneToMany']['translations'] = array(
                'targetEntity' => $meta->getReflectionClass()->getShortName() . 'Translation',
                'mappedBy' => 'object',
                'cascade' => array('persist', 'remove')
            );
        } else {
            $yaml[$meta->name]['fields']['translations'] = array(
                'targetDocument' => $meta->getReflectionClass()->getShortName() . 'Translation',
                'mappedBy' => 'object',
                'type' => 'many',
                'cascade' => array('persist', 'remove')
            );
        }

        // now generate a translation mapping
        $translationFile = dirname($file) . DIRECTORY_SEPARATOR . basename($file, $extension) . 'Translation' . $extension;
        $changed = false;
        $name = $meta->name . 'Translation';
        if (!file_exists($translationFile)) {
            if ($type === 'Entity') {
                $translationYaml = array($name => array(
                    'type' => 'entity',
                    'manyToOne' => array(
                        'object' => array(
                            'targetEntity' => $meta->getReflectionClass()->getShortName(),
                            'joinColumn' => array(
                                'name' => 'object_id',
                                'referencedColumnName' => 'id',
                                'onDelete' => 'CASCADE',
                            ),
                            'inversedBy' => 'translations',
                        )
                    ),
                    'fields' => array()
                ));
            } else {
                $translationYaml = array($name => array(
                    'fields' => array(
                        'object' => array(
                            'targetDocument' => $meta->getReflectionClass()->getShortName(),
                            'type' => 'one',
                            'inversedBy' => 'translations',
                        )
                    )
                ));
            }
            $changed = true;
        } else {
            $translationYaml = Yaml::parse($translationFile);
        }
        // update field mappings
        foreach ($config['fields'] as $field => $options) {
            if (!isset($translationYaml[$name]['fields'][$field])) {
                $fieldMapping = $yaml[$meta->name]['fields'][$field];
                unset($fieldMapping['gedmo']);
                if (isset($fieldMapping['unique'])) {
                    throw new \LogicException("Translatable entity: {$meta->name} cannot have unique translatable field"
                        . ", place unique on {$name} field '{$field}' mapping instead.");
                }
                $translationYaml[$name]['fields'][$field] = $fieldMapping;
                $changed = true;
            }
        }
        // save modifications
        if ($changed && false === file_put_contents($translationFile, Yaml::dump($translationYaml, 120))) {
            throw new \RuntimeException("Could not write file: $translationFile");
        }
        if (!$hasInversedRelation && false === file_put_contents($file, Yaml::dump($yaml, 120))) {
            throw new \RuntimeException("Could not write file: $file");
        }
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
        $hasInversedRelation = isset($yaml[$meta->name]['oneToMany']['translations']) || isset($yaml[$meta->name]['fields']['translations']);
        // add mapping for translations collection
        if ($type === 'Entity') {
            if (!isset($yaml[$meta->name]['oneToMany'])) {
                $yaml[$meta->name]['oneToMany'] = array();
            }
            $yaml[$meta->name]['oneToMany']['translations'] = array(
                'targetEntity' => $meta->getReflectionClass()->getShortName() . 'Translation',
                'mappedBy' => 'object',
                'cascade' => array('persist', 'remove')
            );
        } else {
            $yaml[$meta->name]['fields']['translations'] = array(
                'targetDocument' => $meta->getReflectionClass()->getShortName() . 'Translation',
                'mappedBy' => 'object',
                'type' => 'many',
                'cascade' => array('persist', 'remove')
            );
        }

        // now generate a translation mapping
        $translationFile = dirname($file) . DIRECTORY_SEPARATOR . basename($file, $extension) . 'Translation' . $extension;
        $changed = false;
        $name = $meta->name . 'Translation';
        if (!file_exists($translationFile)) {
            if ($type === 'Entity') {
                $translationYaml = array($name => array(
                    'type' => 'entity',
                    'manyToOne' => array(
                        'object' => array(
                            'targetEntity' => $meta->getReflectionClass()->getShortName(),
                            'joinColumn' => array(
                                'name' => 'object_id',
                                'referencedColumnName' => 'id',
                                'onDelete' => 'CASCADE',
                            ),
                            'inversedBy' => 'translations',
                        )
                    ),
                    'fields' => array()
                ));
            } else {
                $translationYaml = array($name => array(
                    'fields' => array(
                        'object' => array(
                            'targetDocument' => $meta->getReflectionClass()->getShortName(),
                            'type' => 'one',
                            'inversedBy' => 'translations',
                        )
                    )
                ));
            }
            $changed = true;
        } else {
            $translationYaml = Yaml::parse($translationFile);
        }
        // update field mappings
        foreach ($config['fields'] as $field => $options) {
            if (!isset($translationYaml[$name]['fields'][$field])) {
                $fieldMapping = $yaml[$meta->name]['fields'][$field];
                unset($fieldMapping['gedmo']);
                if (isset($fieldMapping['unique'])) {
                    throw new \LogicException("Translatable entity: {$meta->name} cannot have unique translatable field"
                        . ", place unique on {$name} field '{$field}' mapping instead.");
                }
                $translationYaml[$name]['fields'][$field] = $fieldMapping;
                $changed = true;
            }
        }
        // save modifications
        if ($changed && false === file_put_contents($translationFile, Yaml::dump($translationYaml, 120))) {
            throw new \RuntimeException("Could not write file: $translationFile");
        }
        if (!$hasInversedRelation && false === file_put_contents($file, Yaml::dump($yaml, 120))) {
            throw new \RuntimeException("Could not write file: $file");
        }
    }

    private function generateTranslationClass(ObjectManager $om, Driver $driver, array $config, $meta)
    {
        if ($om instanceof EntityManager) {
            $objectType = 'Entity';
            $annotated = $driver instanceof TranslatableAnnotationDriver ? true : false;
            $tpl = $annotated ? self::TRANSLATION_ANNOTATED_ORM_TPL : self::TRANSLATION_TPL;
        } elseif ($om instanceof DocumentManager) {
            $objectType = 'Document';
            $annotated = $driver instanceof TranslatableAnnotationDriver ? true : false;
            $tpl = $annotated ? self::TRANSLATION_ANNOTATED_ODM_TPL : self::TRANSLATION_TPL;
        } else {
            throw new \RuntimeException("Unsupported object manager: ".get_class($om));
        }

        if (!class_exists($config['translationClass'])) {
            $refl = $meta->getReflectionClass();
            $transFileName = dirname($refl->getFileName()) . DIRECTORY_SEPARATOR . $refl->getShortName() . 'Translation.php';
            $replace = array(
                '%ns%' => $refl->getNamespaceName(),
                '%type%' => $objectType,
                '%targetClass%' => $refl->getShortName(),
                '%index%' => 'UNIQ_' . substr(strtoupper(md5($meta->name)), 0, 16),
            );
            if (!file_put_contents($transFileName, str_replace(array_keys($replace), array_values($replace), $tpl))) {
                throw new \RuntimeException("Could not write file: $transFileName");
            }
            require $transFileName;
        }

        $tname = $config['translationClass'];
        $refl = new \ReflectionClass($tname);
        $methodBlock = $fieldBlock = '';
        foreach ($config['fields'] as $field => $options) {
            if (!$refl->hasProperty($field)) {
                $ufield = ucfirst($field);
                $mapping = $meta->getFieldMapping($field);
                $type = $this->getType($mapping['type']);
                $methodBlock .= <<<EOT

    /**
     * Set translation \${$field} field
     *
     * @param {$type} \${$field}
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
     * @return {$type}
     */
    public function get{$ufield}()
    {
        return \$this->{$field};
    }

EOT;
                if ($annotated) {
                    $column = $om instanceof EntityManager ? $this->getOrmColumn($mapping, $meta) : $this->getOdmColumn($mapping, $meta);
                } else {
                    $column = '@var '.$type;
                }

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
            $this->injectInversedRelation($refl->getShortName().'Translation', $refl, $annotated, $objectType);
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
     * @param \\$fullTargetName
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
     * @param \\$fullTargetName
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
                $lines[$num] .= "\n" . $inverseRelationBlock;
                break;
            }
        }
        for ($i = count($lines) - 1; $i !== 0; $i--) {
            if (trim($lines[$i]) === '}') {
                $lines[$i - 1] .= "\n" . rtrim($methodBlock);
                break;
            }
        }
        if (false === file_put_contents($refl->getFileName(), implode("\n", $lines))) {
            throw new \RuntimeException("Could not write file: ".$refl->getFileName().", check permissions");
        }
    }

    private function getOdmColumn(array $mapping, $meta)
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

    private function getOrmColumn(array $mapping, $meta)
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

    private function injectTranslationCode($fields, $methods, $refl)
    {
        $lines = explode("\n", file_get_contents($refl->getFileName()));
        foreach ($lines as $num => $line) {
            if (preg_match('/^protected\s+\$object\s*;$/i', trim($line))) {
                $lines[$num] .= "\n" . $fields;
                break;
            }
        }
        for ($i = count($lines) - 1; $i !== 0; $i--) {
            if (trim($lines[$i]) === '}') {
                $lines[$i - 1] .= rtrim($methods);
                break;
            }
        }
        if (false === file_put_contents($refl->getFileName(), implode("\n", $lines))) {
            throw new \RuntimeException("Could not write file: $transFileName, check permissions");
        }
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
