<?php

namespace DoctrineExtensions\Translatable;

use Doctrine\Common\Util\Debug;

/**
 * These are tests for translatable behavior
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package DoctrineExtensions.Translatable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class EntityTranslationTableTest extends \PHPUnit_Framework_TestCase
{
    const TEST_ENTITY_CLASS = 'DoctrineExtensions\Translatable\Person';
    const TEST_ENTITY_TRANSLATION = 'DoctrineExtensions\Translatable\PersonTranslation';
    const TRANSLATION_CLASS = 'DoctrineExtensions\Translatable\Entity\Translation';
    
    private $translatableListener;
    private $em;

    public function setUp()
    {
        $config = new \Doctrine\ORM\Configuration();
        $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
        $config->setQueryCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
        $config->setProxyDir(__DIR__ . '/temp');
        $config->setProxyNamespace('DoctrineExtensions\Translatable\Proxies');
        $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver());

        $conn = array(
            'driver' => 'pdo_sqlite',
            'memory' => true,
        );

        //$config->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger());
        
        $evm = new \Doctrine\Common\EventManager();
        $this->translatableListener = new TranslationListener();
        $this->translatableListener->setTranslatableLocale('en_us');
        $evm->addEventSubscriber($this->translatableListener);
        $this->em = \Doctrine\ORM\EntityManager::create($conn, $config, $evm);

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
        $schemaTool->createSchema(array(
            $this->em->getClassMetadata(self::TEST_ENTITY_CLASS),
            $this->em->getClassMetadata(self::TEST_ENTITY_TRANSLATION),
            $this->em->getClassMetadata(self::TRANSLATION_CLASS)
        ));
    }
    
    public function testFixtureGeneratedTranslations()
    {
        $person = new Person;
        $person->setName('name in en');
        
        $this->em->persist($person);
        $this->em->flush();
        $this->em->clear();
        
        $repo = $this->em->getRepository(self::TEST_ENTITY_TRANSLATION);
        $this->assertTrue($repo instanceof Repository\TranslationRepository);
        
        $translations = $repo->findTranslations($person);
        $this->assertEquals(count($translations), 1);
        $this->assertArrayHasKey('en_us', $translations);
        
        $this->assertArrayHasKey('name', $translations['en_us']);
        $this->assertEquals('name in en', $translations['en_us']['name']);
        // test second translations
        $person = $this->em->find(self::TEST_ENTITY_CLASS, $person->getId());
        $this->translatableListener->setTranslatableLocale('de_de');
        $person->setName('name in de');
        
        $this->em->persist($person);
        $this->em->flush();
        $this->em->clear();
        
        $translations = $repo->findTranslations($person);
        $this->assertEquals(count($translations), 2);
        $this->assertArrayHasKey('de_de', $translations);
        
        $this->assertArrayHasKey('name', $translations['de_de']);
        $this->assertEquals('name in de', $translations['de_de']['name']);
        
        $this->translatableListener->setTranslatableLocale('en_us');
    }
}

/**
 * @Entity
 */
class Person implements Translatable
{
    /** 
     * @Id 
     * @GeneratedValue 
     * @Column(type="integer")
     */
    private $id;

    /**
     * @Column(name="name", type="string", length=128)
     */
    private $name;

    public function getId()
    {
        return $this->id;
    }
    
    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }
    
    public function getTranslatableFields()
    {
        return array('name');
    }
    
    public function getTranslatableLocale()
    {
        return null;
    }
    
    public function getTranslationEntity()
    {
        return 'DoctrineExtensions\Translatable\PersonTranslation';
    }
}

/**
 * @Table(name="person_translations", indexes={
 *      @index(name="person_translation_idx", columns={"locale", "entity", "foreign_key", "field"})
 * })
 * @Entity(repositoryClass="DoctrineExtensions\Translatable\Repository\TranslationRepository")
 */
class PersonTranslation
{
    /**
     * @var integer $id
     *
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string $locale
     *
     * @Column(name="locale", type="string", length=8)
     */
    private $locale;

    /**
     * @var string $entity
     *
     * @Column(name="entity", type="string", length=255)
     */
    private $entity;

    /**
     * @var string $field
     *
     * @Column(name="field", type="string", length=32)
     */
    private $field;

    /**
     * @var string $foreignKey
     *
     * @Column(name="foreign_key", type="string", length="64")
     */
    private $foreignKey;

    /**
     * @var text $content
     *
     * @Column(name="content", type="text", nullable=true)
     */
    private $content;
    
    /**
     * Get id
     *
     * @return integer $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set locale
     *
     * @param string $locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * Get locale
     *
     * @return string $locale
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Set field
     *
     * @param string $field
     */
    public function setField($field)
    {
        $this->field = $field;
    }

    /**
     * Get field
     *
     * @return string $field
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * Set entity
     *
     * @param string $entity
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;
    }

    /**
     * Get entity
     *
     * @return string $entity
     */
    public function getEntity()
    {
        return $this->entity;
    }
    
    /**
     * Set foreignKey
     *
     * @param string $foreignKey
     */
    public function setForeignKey($foreignKey)
    {
        $this->foreignKey = $foreignKey;
    }

    /**
     * Get foreignKey
     *
     * @return string $foreignKey
     */
    public function getForeignKey()
    {
        return $this->foreignKey;
    }
    
    /**
     * Set content
     *
     * @param text $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * Get content
     *
     * @return text $content
     */
    public function getContent()
    {
        return $this->content;
    }
}
