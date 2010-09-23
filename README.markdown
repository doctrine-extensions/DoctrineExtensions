# Some Doctrine 2 Extensions

This package contains extensions for Doctrine 2 that hook into the facilities of Doctrine and
offer new functionality or tools to use Doctrine 2 more efficently.

## Including DoctrineExtensions

To include the DoctrineExtensions should fire up an autoloader, for example:

    $classLoader = new \Doctrine\Common\ClassLoader('DoctrineExtensions', "/path/to/extensions");
    $classLoader->register();

## Translatable

### Recent Updates

- Implemented more abstracted functionality for getting and setting field properties.
- Added support for string primary keys, now translations can use integer and string foreign keys.
- Added support for multiple updates and inserts during single flush.
- Implemented the default locale functionality. Now you can set the default locale to be used then translation
is not found for a globally used locale.
- Added support for multiple field translations for a single Entity.

**Translatable** behavior offers a very handy solution for translating specific record fields
in diferent languages. Further more, it loads the translations automatically for a locale
currently used, which can be set to **Translatable Listener** on it`s initialization or later
for other cases through the **Entity** itself.

This behavior requires an additional metadata path to be specified in order to have a translation
table and translation Entity available. To configure it correctly you can either 1) add new annotation
driver into driver chain or 2) if you allready are using the annotation driver, simply add the path to
the Translatable extension Translation entity:

### Annotation driver mapped into driver chain

    $chainDriverImpl = new Doctrine\ORM\Mapping\Driver\DriverChain();
    $yourDefaultDriverImpl = new Doctrine\ORM\Mapping\Driver\YamlDriver('/yml/mapping/files');
    $translatableDriverImpl = $doctrineOrmConfig->newDefaultAnnotationDriver(
        'path/to/extensions/DoctrineExtensions/Translatable/Entity'
    );
    $chainDriverImpl->addDriver($yourDefaultDriverImpl, 'Entities');
    $chainDriverImpl->addDriver($translatableDriverImpl, 'DoctrineExtensions/Translatable');
    $doctrineOrmConfig->setMetadataDriverImpl($chainDriverImpl);

### Additional metadata mapping path for existing Annotation driver:

    $driverImpl = $doctrineOrmConfig->newDefaultAnnotationDriver(array(
        'default/annotation/entities/path', 
        'path/to/extensions/DoctrineExtensions/Translatable/Entity'
    ));

To attach the **Translatable Listener** to your event system and to set the translation locale
to be used in global scope for all Entities do:

    $evm = new Doctrine\Common\EventManager();
    $translatableListener = new DoctrineExtensions\Translatable\TranslationListener();
    $translatableListener->setTranslatableLocale('en_us');
    // in real world app the locale should be loaded from session, example:
    // Session::getInstance()->read('locale');
    $evm->addEventSubscriber($translatableListener);
    // now this event manager should be passed to entity manager constructor
    
#### An Usage Example

Article Entity:

    namespace Entities;

    use DoctrineExtensions\Translatable\Translatable;
    /**
     * @Table(name="articles")
     * @Entity
     */
    class Article implements Translatable
    {
        /**
         * @Column(name="id", type="integer")
         * @Id
         * @GeneratedValue(strategy="IDENTITY")
         */
        private $id;
    
        /**
         * @Column(name="title", type="string", length=128)
         */
        private $title;
        
        /*
         * Used locale to override Translation listener`s locale
         */
        private $_locale;
    
        /**
         * @Column(name="content", type="text")
         */
        private $content;
    
        public function getId()
        {
            return $this->id;
        }
    
        public function setTitle($title)
        {
            $this->title = $title;
        }
    
        public function getTitle()
        {
            return $this->title;
        }
    
        public function setContent($content)
        {
            $this->content = $content;
        }
    
        public function getContent()
        {
            return $this->content;
        }
        
        public function getTranslatableFields()
        {
            return array('title', 'content');
        }
        
        public function setTranslatableLocale($locale)
        {
            $this->_locale = $locale;
        }
        
        public function getTranslatableLocale()
        {
            return $this->_locale;
        }
    }    

To save article with its translations:

    $article = new Entities\Article;
    $article->setTitle('my title in en');
    $article->setContent('my content in en');
    $em->persist($article);
    $em->flush();
    
Update in other locale:

    $article->setTitle('my title in de');
    $article->setContent('my content in de');
    $article->setTranslatableLocale('de_de');
    $this->_em->persist($article);
    $this->_em->flush();
    
Now then you load your article, it will be translated in used locale:

    $article = $em->getRepository('Entities\Article')->find(1/* id of article */);
    echo $article->getTitle();
    // prints: "my title in en"
    
All translations can be loaded using TranslationRepository:

    $repository = $em->getRepository('DoctrineExtensions\Translatable\Entity\Translation');
    $translations = $repository->findTranslations($article);
    // $translations contains:
    Array (
        [de_de] => Array
            (
                [title] => my title in de
                [content] => my content in de
            )
    
        [en_us] => Array
            (
                [title] => my title in en
                [content] => my content in en
            )
    )
Setting the __default locale__, which will prevent the original value from updating
to new one and let the default translation to be available if record does not
have translation on currently used locale.

    $translationListener->setDefaultLocale('en_us');
    $article->setTitle('my title in ru');
    $article->setContent('my content in ru');
    $article->setTranslatableLocale('ru_ru');
    $this->_em->persist($article);
    $this->_em->flush();
    
After these changes translations will be generated, but article in database
will not change it`s title to "my title in ru". Nevertheless translations in
ru_ru locale will be available to it.

**Translatable** behavior now works on concurent update and insert flush nested
together with **Sluggable** behavior. That means you can persist whatever you
want and on flush everything magicaly will be created and updated..

## Sluggable

### Recent Updates

- Implemented more abstracted functionality for getting and setting field properties.
- Implemented slug type case and separator configuration. Example camel cased and underscored
slug: Example_Slug, default: example-slug.
- Added support for a limited unique slug generation.

**Sluggable** behavior will build the slug of predefined fields on a given field
which should store the slug. Also this behavior can be nested with **Translatable**
behavior to translate the generated slugs into diferent languages.

Entities which should behave like **Sluggable** must implement the **Sluggable interface**.

To attach the **SluggableListener** to your event system use:

    $evm = new Doctrine\Common\EventManager();
    $sluggableListener = new SluggableListener();
    $evm->addEventSubscriber($sluggableListener);
    // now this event manager should be passed to entity manager constructor

If you want to attach **TranslationListener** also add it to EventManager after
the **SluggableListener**. It is important because slug must be generated first
before the creation of it`s translation.

    $evm = new Doctrine\Common\EventManager();
    $sluggableListener = new SluggableListener();
    $evm->addEventSubscriber($sluggableListener);
    $translatableListener = new DoctrineExtensions\Translatable\TranslationListener();
    $translatableListener->setTranslatableLocale('en_us');
    $evm->addEventSubscriber($translatableListener);
    // now this event manager should be passed to entity manager constructor
    
#### An Usage Example

    /**
     * @Entity
     */
    class Article implements Sluggable
    {
        /** @Id @GeneratedValue @Column(type="integer") */
        private $id;
    
        /**
         * @Column(name="title", type="string", length=64)
         */
        private $title;
    
        /**
         * @Column(name="code", type="string", length=16)
         */
        private $code;
        
        /**
         * @Column(name="slug", type="string", length=128)
         */
        private $slug;
    
        public function getId()
        {
            return $this->id;
        }
        
        public function setTitle($title)
        {
            $this->title = $title;
        }
    
        public function getTitle()
        {
            return $this->title;
        }
    
        public function setCode($code)
        {
            $this->code = $code;
        }
    
        public function getCode()
        {
            return $this->code;
        }
        
        public function getSluggableConfiguration()
        {
            $config = new Configuration();
            $config->setSluggableFields(array('title', 'code'));
            $config->setSlugField('slug');
            $config->setLength(64);
            return $config;
        }
        
        public function getSlug()
        {
            return $this->slug;
        }
    }

To save **Article** and generate slug simply use:

    $article = new Article();
    $article->setTitle('the title');
    $article->setCode('my code');    
    $this->em->persist($article);
    $this->em->flush();

    echo $article->getSlug();
    // prints: the-title-my-slug
    
    