# Install Gedmo Doctrine2 extensions in Symfony 4

Configure full featured [Doctrine2 extensions](http://github.com/Atlantic18/DoctrineExtensions) for your symfony 4 project.
This post will show you - how to create a simple configuration file to manage extensions with
ability to use all features it provides.
Interested? then bear with me! and don't be afraid, we're not diving into security component :)

This post will put some light over the shed of extension installation and mapping configuration
of Doctrine2. It does not require any additional dependencies and gives you full power
over management of extensions.

Content:

- [Symfony 4](#sf4-app) application
- Extensions metadata [mapping](#ext-mapping)
- Extension [listeners](#ext-listeners)
- Usage [example](#ext-example)
- Some [tips](#more-tips)
- [Alternative](#alternative) over configuration

<a name="sf4-app"></a>

## Symfony 4 application

First of all, we will need a symfony 4 startup application, let's say [symfony-standard edition
with composer](https://symfony.com/doc/current/best_practices/creating-the-project.html)

- `composer create-project symfony/skeleton [project name]`

Now let's add the **gedmo/doctrine-extensions**

You can find the doctrine-extensions project on packagist: https://packagist.org/packages/gedmo/doctrine-extensions

To add it to your project: 
- `composer require gedmo/doctrine-extensions`

<a name="ext-mapping"></a>

## Mapping

Let's start from the mapping. In case you use the **translatable**, **tree** or **loggable**
extension you will need to map those abstract mapped superclasses for your ORM to be aware of.
To do so, add some mapping info to your **doctrine.orm** configuration, edit **app/config/config.yml**:

```yaml
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
```

After that, running **php bin/console doctrine:mapping:info** you should see the output:

```
Found 3 entities mapped in entity manager default:
[OK]   Gedmo\Translatable\Entity\MappedSuperclass\AbstractPersonalTranslation
[OK]   Gedmo\Translatable\Entity\MappedSuperclass\AbstractTranslation
[OK]   Gedmo\Translatable\Entity\Translation
```
Well, we mapped only **translatable** for now, it really depends on your needs, which extensions
your application uses.

**Note:** there is **Gedmo\Translatable\Entity\Translation** which is not a super class, in that case
if you create a doctrine schema, it will add **ext_translations** table, which might not be useful
to you also. To skip mapping of these entities, you can map **only superclasses**

```yaml
mappings:
    translatable:
        type: annotation
        alias: Gedmo
        prefix: Gedmo\Translatable\Entity
        # make sure vendor library location is correct
        dir: "%kernel.root_dir%/../vendor/gedmo/doctrine-extensions/lib/Gedmo/Translatable/Entity/MappedSuperclass"
```

The configuration above, adds a **/MappedSuperclass** into directory depth, after running
**php bin/console doctrine:mapping:info** you should only see now:

```
Found 2 entities mapped in entity manager default:
[OK]   Gedmo\Translatable\Entity\MappedSuperclass\AbstractPersonalTranslation
[OK]   Gedmo\Translatable\Entity\MappedSuperclass\AbstractTranslation
```

This is very useful for advanced requirements and quite simple to understand. So now let's map
everything the extensions provide:

```yaml
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
```

<a name="ext-listeners"></a>

## Doctrine extension listener services

Next, the heart of extensions are behavioral listeners which pours all the sugar. We will
create a **yml** service file in our config directory. The setup can be different, your config could be located
in the bundle, it depends on your preferences. Edit **app/config/packages/doctrine_extensions.yml**

```yaml
# services to handle doctrine extensions
# import it in config.yml
services:
    # Doctrine Extension listeners to handle behaviors
    gedmo.listener.tree:
        class: Gedmo\Tree\TreeListener
        tags:
            - { name: doctrine.event_subscriber, connection: default }
        calls:
            - [ setAnnotationReader, [ "@annotation_reader" ] ]

    Gedmo\Translatable\TranslatableListener:
        tags:
            - { name: doctrine.event_subscriber, connection: default }
        calls:
            - [ setAnnotationReader, [ "@annotation_reader" ] ]
            - [ setDefaultLocale, [ %locale% ] ]
            - [ setTranslationFallback, [ false ] ]

    gedmo.listener.timestampable:
        class: Gedmo\Timestampable\TimestampableListener
        tags:
            - { name: doctrine.event_subscriber, connection: default }
        calls:
            - [ setAnnotationReader, [ "@annotation_reader" ] ]

    gedmo.listener.sluggable:
        class: Gedmo\Sluggable\SluggableListener
        tags:
            - { name: doctrine.event_subscriber, connection: default }
        calls:
            - [ setAnnotationReader, [ "@annotation_reader" ] ]

    gedmo.listener.sortable:
        class: Gedmo\Sortable\SortableListener
        tags:
            - { name: doctrine.event_subscriber, connection: default }
        calls:
            - [ setAnnotationReader, [ "@annotation_reader" ] ]

    Gedmo\Loggable\LoggableListener:
        tags:
            - { name: doctrine.event_subscriber, connection: default }
        calls:
            - [ setAnnotationReader, [ "@annotation_reader" ] ]

    Gedmo\Blameable\BlameableListener:
        tags:
            - { name: doctrine.event_subscriber, connection: default }
        calls:
            - [ setAnnotationReader, [ "@annotation_reader" ] ]

```

So what does it include in general? Well, it creates services for all extension listeners.
You can remove some which you do not use, or change them as you need. **Translatable** for instance,
sets the default locale to the value of your `%locale%` parameter, you can configure it differently.

**Note:** In case you noticed, there is **EventSubscriber\DoctrineExtensionSubscriber**.
You will need to create this subscriber class if you use **loggable** , **translatable** or **blameable**
behaviors. This listener will set the **locale used** from request and **username** to
loggable and blameable. So, to finish the setup create **EventSubscriber\DoctrineExtensionSubscriber**

```php
<?php

namespace App\EventSubscriber;

use Gedmo\Blameable\BlameableListener;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class DoctrineExtensionSubscriber implements EventSubscriberInterface
{
    /**
     * @var BlameableListener
     */
    private $blameableListener;
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;
    /**
     * @var TranslatableListener
     */
    private $translatableListener;
    /**
     * @var LoggableListener
     */
    private $loggableListener;


    public function __construct(
        BlameableListener $blameableListener,
        TokenStorageInterface $tokenStorage,
        TranslatableListener $translatableListener,
        LoggableListener $loggableListener
    ) {
        $this->blameableListener = $blameableListener;
        $this->tokenStorage = $tokenStorage;
        $this->translatableListener = $translatableListener;
        $this->loggableListener = $loggableListener;
    }    


    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
            KernelEvents::FINISH_REQUEST => 'onLateKernelRequest'
        ];
    }
    public function onKernelRequest(): void
    {
        if ($this->tokenStorage !== null &&
            $this->tokenStorage->getToken() !== null &&
            $this->tokenStorage->getToken()->isAuthenticated() === true
        ) {
            $this->blameableListener->setUserValue($this->tokenStorage->getToken()->getUser());
        }
    }
    
    public function onLateKernelRequest(FinishRequestEvent $event): void
    {
        $this->translatableListener->setTranslatableLocale($event->getRequest()->getLocale());
    }

}
```

<a name="ext-example"></a>

## Example

After that, you have your extensions set up and ready to be used! Too easy right? Well,
if you do not believe me, let's create a simple entity in our **Acme** project:

```php

<?php

// file: src/Entity/BlogPost.php

namespace App\Entity;

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
```

Now, let's have some fun:

- if you have not created the database yet, run `php bin/console doctrine:database:create`
- create the schema `php bin/console doctrine:schema:create`

Everything will work just fine, you can modify the **App\Controller\DemoController**
and add an action to test how it works:

```php
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
```

Now if you follow the url: **http://your_virtual_host/app_dev.php/demo/posts** you
should see a print of posts, this is only an extension demo, we will not create a template.

<a name="more-tips"></a>

## More tips

Regarding, the setup, I do not think it's too complicated to use, in general it is simple
enough, and lets you understand at least small parts on how you can hook mappings into doctrine, and
how easily extension services are added. This configuration does not hide anything behind
curtains and allows you to modify the configuration as you require.

### Multiple entity managers

If you use more than one entity manager, you can simply tag the subscriber
with other the manager name:


Regarding, mapping of ODM mongodb, it's basically the same:

```yaml
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
```

This also shows, how to make mappings based on single manager. All what differs is that **Document**
instead of **Entity** is used. I haven't tested it with mongo though.

**Note:** [extension repository](http://github.com/Atlantic18/DoctrineExtensions) contains all
[documentation](http://github.com/Atlantic18/DoctrineExtensions/tree/master/doc) you may need
to understand how you can use it in your projects.

<a name="alternative"></a>

## Alternative over configuration

You can use [StofDoctrineExtensionsBundle](http://github.com/stof/StofDoctrineExtensionsBundle) which is a wrapper of these extensions

## Troubleshooting

- Make sure there are no *.orm.yml or *.orm.xml files for your Entities in your bundles Resources/config/doctrine directory. With those files in place the annotations won't be taken into account.
