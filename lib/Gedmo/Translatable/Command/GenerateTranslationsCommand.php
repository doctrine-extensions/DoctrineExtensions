<?php

namespace Gedmo\Translatable\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManager;

use Gedmo\Mapping\Driver;
use Gedmo\Mapping\Driver\Chain as DriverChain;
use Gedmo\Mapping\ExtensionMetadataFactory;
use Gedmo\Translatable\TranslatableListener;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

class GenerateTranslationsCommand extends Command
{
    const TRANSLATION_TPL = <<<EOT
<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Translatable\Entity\MappedSuperclass\AbstractTranslation;

/**
 * @ORM\Entity
 * @ORM\Table(uniqueConstraints={
 *   @ORM\UniqueConstraint(name="_IDX_", columns={"locale", "object_id"})
 * })
 */
class _TARGET_ENTITY_Translation extends AbstractTranslation
{
    /**
     * @ORM\ManyToOne(targetEntity="_TARGET_ENTITY_", inversedBy="translations")
     * @ORM\JoinColumn(name="object_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    protected \$object;

    _FIELDS_

    _CONSTRUCTOR_

    _METHODS_
}
EOT;

    protected $em;

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
        $this->em = $this->getHelper('em')->getEntityManager();
        $this->generate();
    }

    protected function generate()
    {
        $emf = new ExtensionMetadataFactory($this->em, 'Gedmo\Translatable', $this->getAnnotationReader());
        $metadatas = $this->em->getMetadataFactory()->getAllMetadata();
        foreach ($metadatas as $meta) {
            try {
                $config = $emf->getExtensionMetadata($meta);
                // whether to update or create
                $config['translationReflection'] = new \ReflectionClass($config['translationClass']);
            } catch (InvalidMappingException $e) {
                $config = $e->currentConfig;
            }
            if ($config && ($driver = $this->getExtensionDriverUsed($emf, $meta))) {
                $driverType = end($parts = explode('\\', get_class($driver)));
                switch ($driverType) {
                    case "Annotation":
                        $this->generateAnnotatedTranslationClass($driver, $config, $meta);
                        break;
                    case "Xml":
                        break;
                    case "Yaml":
                        break;
                }
                var_dump($config, $meta->name, $driverType);
            }
        }
    }

    private function generateAnnotatedTranslationClass(Driver $driver, array $config, ClassMetadataInfo $meta)
    {
        if (!$config['translationReflection']) {
            $targetEntityFile = $meta->getReflectionClass()->getFileName();
            var_dump($targetEntityFile);
        }
    }

    private function getExtensionDriverUsed(ExtensionMetadataFactory $emf, ClassMetadataInfo $meta)
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
}
