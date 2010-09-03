# Some Doctrine 2 Extensions

This package contains extensions for Doctrine 2 that hook into the facilities of Doctrine and
offer new functionality or tools to use Doctrine 2 more efficently.

## Including DoctrineExtensions

To include the DoctrineExtensions should fire up an autoloader, for example:

    $classLoader = new \Doctrine\Common\ClassLoader('DoctrineExtensions', "/path/to/extensions");
    $classLoader->register();
    
## Translatable

**Translatable** behavior offers a very handy solution for translating specific record fields
in diferent languages. Further more, it loads the translations automatically for a locale
currently used, which can be set to **Translatable Listener** on it`s initialization or later
for other cases through the entity itself.

This behavior requires an additional metadata path to be specified in order to have a translation
table and translation entity available. To configure the it correctly you can either 1) add annotation
driver into driver chain or 2) if you allready are using the annotation driver, simply add the path to
the Translatable extension Translation entity:

1. Annotation driver mapped into driver chain

    $chainDriverImpl = new Doctrine\ORM\Mapping\Driver\DriverChain();
    $yourDefaultDriverImpl = new Doctrine\ORM\Mapping\Driver\YamlDriver('/yml/mapping/files');
    $translatableDriverImpl = $doctrineOrmConfig->newDefaultAnnotationDriver('path/to/extensions/DoctrineExtensions/Translatable/Entity');
    $chainDriverImpl->addDriver($yourDefaultDriverImpl, 'Entities');
    $chainDriverImpl->addDriver($translatableDriverImpl, 'DoctrineExtensions/Translatable');
    $doctrineOrmConfig->setMetadataDriverImpl($chainDriverImpl);

2. Another path for Annotation driver:

    $driverImpl = $doctrineOrmConfig->newDefaultAnnotationDriver(array('default/annotation/entities/path', 'path/to/extensions/DoctrineExtensions/Translatable/Entity'));

To attach the Translatable listener to your event system and to set the translation locale
to use in global scope for all entities:

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
    
