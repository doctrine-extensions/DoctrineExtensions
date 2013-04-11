# Install Gedmo Doctrine2 extensions in Symfony2

Configure full featured [Doctrine2 extensions](http://github.com/l3pp4rd/DoctrineExtensions) for your symfony2 project.
This post will show you - how to create a simple configuration file to manage extensions with
ability to use all features it provides.
Interested? then bear with me! and don't be afraid, we're not diving into security component :)

[blog_reference]: http://gediminasm.org/article/mapping-extension-for-doctrine2 "Mapping extension for Doctrine2 makes it easy to create extensions based on annotation, xml, yaml mapping drivers"
[blog_test]: http://gediminasm.org/test "Test extensions on this blog"

This post will put some light over the shed of extension installation and mapping configuration
of Doctrine2. It does not require any additional dependencies and gives you full power
over management of extensions.

Content:
    
- [Symfony2](#sf2-app) application
- Extensions metadata [mapping](#ext-mapping)
- Extension [listeners](#ext-listeners)
- Usage [example](#ext-example)
- Some [tips](#more-tips)
- [Alternative](#alternative) over configuration

<a name="sf2-app"></a>

## Symfony2 application

First of all, we will need a symfony2 startup application, lets say [symfony-standard edition
with composer](http://github.com/KnpLabs/symfony-with-composer). Follow the standard setup:

- `git clone git://github.com/KnpLabs/symfony-with-composer.git example`
- `cd example && rm -rf .git && php bin/vendors install`
- ensure your application loads and meet requirements, by following the url: **http://your_virtual_host/app_dev.php**

Now lets add the **gedmo/doctrine-extensions** into **composer.json**

    {
        "require": {
            "php":              ">=5.3.2",
            "symfony/symfony":  ">=2.0.9,<2.1.0-dev",
            "doctrine/orm":     ">=2.1.0,<2.2.0-dev",
            "twig/extensions":  "*",
    
            "symfony/assetic-bundle":         "*",
            "sensio/generator-bundle":        "2.0.*",
            "sensio/framework-extra-bundle":  "2.0.*",
            "sensio/distribution-bundle":     "2.0.*",
            "jms/security-extra-bundle":      "1.0.*",
            "gedmo/doctrine-extensions":      "dev-master"
        },
    
        "autoload": {
            "psr-0": {
                "Acme": "src/"
            }
        }
    }

Update vendors, run: **php bin/vendors update**
Initially in this package you have **doctrine2 orm** included, so we will base our setup
and configuration for this specific connection. Do not forget to configure your database
connection parameters, edit **app/config/parameters.ini**

<a name="ext-mapping"></a>

## Mapping

Lets start from the mapping, in case if you use **translatable**, **tree** or **loggable**
extension you will need to map those abstract mappedsuperclasses for you ORM to be aware of.
To do so, add some mapping info to your **doctrine.orm** configuration, edit **app/config/config.yml**:

    doctrine:
        dbal:
    # your dbal config here
    
        orm:
            auto_generate_proxy_classes: %kernel.debug%
            auto_mapping: true
    # only these lines are added additionally 
            mappings:
                translatable:
                    type: annotation
                    alias: Gedmo
                    prefix: Gedmo\Translatable\Entity
                    # make sure vendor library location is correct
                    dir: "%kernel.root_dir%/../vendor/gedmo/doctrine-extensions/lib/Gedmo/Translatable/Entity"

After that, running **php app/console doctrine:mapping:info** you should see the output:

    Found 3 entities mapped in entity manager default:
    [OK]   Gedmo\Translatable\Entity\MappedSuperclass\AbstractPersonalTranslation
    [OK]   Gedmo\Translatable\Entity\MappedSuperclass\AbstractTranslation
    [OK]   Gedmo\Translatable\Entity\Translation

Well we mapped only **translatable** for now, it really depends on your needs, which extensions
your application uses.

**Note:** there is **Gedmo\Translatable\Entity\Translation** which is not a super class, in that case
if you create doctrine schema, it will add **ext_translations** table, which might not be useful
to you also. To skip mapping of these entities, you can map **only superclasses**

    mappings:
        translatable:
            type: annotation
            alias: Gedmo
            prefix: Gedmo\Translatable\Entity
            # make sure vendor library location is correct
            dir: "%kernel.root_dir%/../vendor/gedmo/doctrine-extensions/lib/Gedmo/Translatable/Entity/MappedSuperclass"

The configuration above, adds a **/MappedSuperclass** into directory depth, after running
**php app/console doctrine:mapping:info** you should only see now:

    Found 2 entities mapped in entity manager default:
    [OK]   Gedmo\Translatable\Entity\MappedSuperclass\AbstractPersonalTranslation
    [OK]   Gedmo\Translatable\Entity\MappedSuperclass\AbstractTranslation

This is very useful for advanced requirements and quite simple to understand. So lets map now
everything extensions provide:

    # only orm config branch of doctrine
    orm:
        auto_generate_proxy_classes: %kernel.debug%
        auto_mapping: true
    # only these lines are added additionally 
        mappings:
            translatable:
                type: annotation
                alias: Gedmo
                prefix: Gedmo\Translatable\Entity
                # make sure vendor library location is correct
                dir: "%kernel.root_dir%/../vendor/gedmo/doctrine-extensions/lib/Gedmo/Translatable/Entity"
            loggable:
                type: annotation
                alias: Gedmo
                prefix: Gedmo\Loggable\Entity
                dir: "%kernel.root_dir%/../vendor/gedmo/doctrine-extensions/lib/Gedmo/Loggable/Entity"
            tree:
                type: annotation
                alias: Gedmo
                prefix: Gedmo\Tree\Entity
                dir: "%kernel.root_dir%/../vendor/gedmo/doctrine-extensions/lib/Gedmo/Tree/Entity"

<a name="ext-listeners"></a>

## Doctrine extension listener services

Next, the heart of extensions are behavioral listeners which pours all the sugar. We will
create a **yml** service file in our config directory. The setup can be different and located
in the bundle, it depends what you prefer, edit **app/config/doctrine_extensions.yml**

    # services to handle doctrine extensions
    # import it in config.yml
    services:
        # KernelRequest listener
        extension.listener:
            class: Acme\DemoBundle\Listener\DoctrineExtensionListener
            calls:
                - [ setContainer, [ @service_container ] ]
            tags:
                # translatable sets locale after router processing
                - { name: kernel.event_listener, event: kernel.request, method: onLateKernelRequest, priority: -10 }
                # loggable hooks user username if one is in security context
                - { name: kernel.event_listener, event: kernel.request, method: onKernelRequest }
    
    
        # Doctrine Extension listeners to handle behaviors
        gedmo.listener.tree:
            class: Gedmo\Tree\TreeListener
            tags:
                - { name: doctrine.event_subscriber, connection: default }
            calls:
                - [ setAnnotationReader, [ @annotation_reader ] ]
                
        gedmo.listener.translatable:
            class: Gedmo\Translatable\TranslatableListener
            tags:
                - { name: doctrine.event_subscriber, connection: default }
            calls:
                - [ setAnnotationReader, [ @annotation_reader ] ]
                - [ setDefaultLocale, [ %locale% ] ]
                - [ setTranslationFallback, [ false ] ]
        
        gedmo.listener.timestampable:
            class: Gedmo\Timestampable\TimestampableListener
            tags:
                - { name: doctrine.event_subscriber, connection: default }
            calls:
                - [ setAnnotationReader, [ @annotation_reader ] ]
        
        gedmo.listener.sluggable:
            class: Gedmo\Sluggable\SluggableListener
            tags:
                - { name: doctrine.event_subscriber, connection: default }
            calls:
                - [ setAnnotationReader, [ @annotation_reader ] ]
        
        gedmo.listener.sortable:
            class: Gedmo\Sortable\SortableListener
            tags:
                - { name: doctrine.event_subscriber, connection: default }
            calls:
                - [ setAnnotationReader, [ @annotation_reader ] ]
        
        gedmo.listener.loggable:
            class: Gedmo\Loggable\LoggableListener
            tags:
                - { name: doctrine.event_subscriber, connection: default }
            calls:
                - [ setAnnotationReader, [ @annotation_reader ] ]

So what it includes in general? Well it creates services for all extension listeners.
You can remove some which you do not use or change at will. **Translatable** for instance,
sets default locale to `%locale%` parameter, you can configure it differently.

**Note:** if you noticed, theres **Acme\DemoBundle\Listener\DoctrineExtensionListener**
you will need to create this listener class if you use **loggable** or **translatable**
behaviors. This listener will set the **locale used** from request and **username** to
loggable. So, to finish the setup create **Acme\DemoBundle\Listener\DoctrineExtensionListener**

    <?php
    
    // file: src/Acme/DemoBundle/Listener/DoctrineExtensionListener.php
    
    namespace Acme\DemoBundle\Listener;
    
    use Symfony\Component\HttpKernel\Event\GetResponseEvent;
    use Symfony\Component\DependencyInjection\ContainerAwareInterface;
    use Symfony\Component\DependencyInjection\ContainerInterface;
    
    class DoctrineExtensionListener implements ContainerAwareInterface
    {
        /**
         * @var ContainerInterface
         */
        protected $container;
    
        public function setContainer(ContainerInterface $container = null)
        {
            $this->container = $container;
        }
    
        public function onLateKernelRequest(GetResponseEvent $event)
        {
            $translatable = $this->container->get('gedmo.listener.translatable');
            $translatable->setTranslatableLocale($event->getRequest()->getLocale());
        }
    
        public function onKernelRequest(GetResponseEvent $event)
        {
            $securityContext = $this->container->get('security.context', ContainerInterface::NULL_ON_INVALID_REFERENCE);
            if (null !== $securityContext && null !== $securityContext->getToken() && $securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
                $loggable = $this->container->get('gedmo.listener.loggable');
                $loggable->setUsername($securityContext->getToken()->getUsername());
            }
        }
    }

Do not forget to import **doctrine_extensions.yml** in your **app/config/config.yml** etc.:

    # file: app/config/config.yml
    imports:
        - { resource: parameters.yml }
        - { resource: security.yml }
        - { resource: doctrine_extensions.yml }
    
    # ... configuration follows

<a name="ext-example"></a>

## Example

Well after that, you have your extensions setup and ready to be used! Too easy right? Well
if you do not believe me, lets create a simple entity in our **Acme** project:

    <?php
    
    // file: src/Acme/DemoBundle/Entity/BlogPost.php
    
    namespace Acme\DemoBundle\Entity;
    
    use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations
    use Doctrine\ORM\Mapping as ORM; // doctrine orm annotations
    
    /**
     * @ORM\Entity
     */
    class BlogPost
    {
        /**
         * @Gedmo\Slug(fields={"title"}, updatable=false, separator="_")
         * @ORM\Id
         * @ORM\Column(length=32, unique=true)
         */
        private $id;
    
        /**
         * @Gedmo\Translatable
         * @ORM\Column(length=64)
         */
        private $title;
    
        /**
         * @Gedmo\Timestampable(on="create")
         * @ORM\Column(name="created", type="datetime")
         */
        private $created;
    
        /**
         * @ORM\Column(name="updated", type="datetime")
         * @Gedmo\Timestampable(on="update")
         */
        private $updated;
    
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
    
        public function getCreated()
        {
            return $this->created;
        }
    
        public function getUpdated()
        {
            return $this->updated;
        }
    }

Now, lets have some fun:

- if you have not created database yet, run `php app/console doctrine:database:create`
- create the schema `php app/console doctrine:schema:create`

Well, everything will work just fine, you can modify the **Acme\DemoBundle\Controller\DemoController**
and add an action to test how it works:

    // file: src/Acme/DemoBundle/Controller/DemoController.php
    // include this code portion
    
    /**
     * @Route("/posts", name="_demo_posts")
     */
    public function postsAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        $repository = $em->getRepository('AcmeDemoBundle:BlogPost');
        // create some posts in case if there aren't any
        if (!$repository->findOneById('hello_world')) {
            $post = new \Acme\DemoBundle\Entity\BlogPost();
            $post->setTitle('Hello world');

            $next = new \Acme\DemoBundle\Entity\BlogPost();
            $next->setTitle('Doctrine extensions');

            $em->persist($post);
            $em->persist($next);
            $em->flush();
        }
        $posts = $em
            ->createQuery('SELECT p FROM AcmeDemoBundle:BlogPost p')
            ->getArrayResult()
        ;
        die(var_dump($posts));
    }

Now if you follow the url: **http://your_virtual_host/app_dev.php/demo/posts** you
should see a print of posts, this is only an extension demo, we will not create template.

<a name="more-tips"></a>

## More tips

Regarding, the setup, I do not think its too complicated to use, in general it is simple
enough, and lets you understand at least small parts on how you can hook mapping into doctrine,
how easily extension services are added. This configuration does not hide anything behind
curtains and allows you to modify the configuration as you require.

### Multiple entity managers

If you use more than one entity manager, you can simply tag the listener
with other manager name:

    services:
        # tree behavior
        gedmo.listener.tree:
            class: Gedmo\Tree\TreeListener
            tags:
                - { name: doctrine.event_subscriber, connection: default }
                # additional ORM subscriber
                - { name: doctrine.event_subscriber, connection: other_connection }
                # ODM MongoDb subscriber, where **default** is manager name
                - { name: doctrine_mongodb.odm.event_subscriber }
            calls:
                - [ setAnnotationReader, [ @annotation_reader ] ]

Well regarding, mapping of ODM mongodb, its basically the same:

    doctrine_mongodb:
        default_database: 'my_database'
        default_connection: 'default'
        default_document_manager: 'default'
        connections:
            default: ~
        document_managers:
            default:
                connection: 'default'
                auto_mapping: true
                mappings:
                    translatable:
                        type: annotation
                        alias: GedmoDocument
                        prefix: Gedmo\Translatable\Document
                        # make sure vendor library location is correct
                        dir: "%kernel.root_dir%/../vendor/gedmo/doctrine-extensions/lib/Gedmo/Translatable/Document"

This also shows, how to make mappings based on single manager. All what differs is **Document**
instead of **Entity** used. Haven't tested it with mongo though.

**Note:** [extension repository](http://github.com/l3pp4rd/DoctrineExtensions) contains all
[documentation](http://github.com/l3pp4rd/DoctrineExtensions/tree/master/doc) you may need
to understand how you can use it in your projects.

<a name="alternative"></a>

## Alternative over configuration

You can use [StofDoctrineExtensionsBundle](http://github.com/stof/StofDoctrineExtensionsBundle) which is a wrapper of these extensions
