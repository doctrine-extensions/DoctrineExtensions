# Reference Integrity behavior extension for Doctrine 2

**ReferenceIntegrity** behavior will automate the reference integrity for referenced documents.
It works through annotations and yaml, and supports 'nullify' and 'restrict' which throws an exception.

So let's say you have a Type which is referenced to multiple Articles, when deleting the Type, by default the Article
would still have a reference to Type, since Mongo doesn't care. When setting the ReferenceIntegrity to 'nullify' it
would then automatically remove the reference from Article.

Features:

- Automatically remove referenced association
- ODM only
- ReferenceOne and ReferenceMany support
- 'nullify' and 'restrict' support
- Annotation and Yaml mapping support for extensions


**Symfony:**

- **ReferenceIntegrity** is available as [Bundle](http://github.com/stof/StofDoctrineExtensionsBundle)
for **Symfony2**, together with all other extensions

This article will cover the basic installation and functionality of **ReferenceIntegrity** behavior

Content:

- [Including](#including-extension) the extension
- Document [example](#document-mapping)
- [Yaml](#yaml-mapping) mapping example
- Usage [examples](#advanced-examples)

<a name="including-extension"></a>

## Setup and autoloading

Read the [documentation](http://github.com/l3pp4rd/DoctrineExtensions/blob/master/doc/annotations.md#em-setup)
or check the [example code](http://github.com/l3pp4rd/DoctrineExtensions/tree/master/example)
on how to setup and use the extensions in most optimized way.

<a name="document-mapping"></a>

## ReferenceIntegrity Document example:

``` php
<?php
namespace Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ODM\Document(collection="types")
 */
class Type
{
    /**
     * @ODM\Id
     */
    private $id;

    /**
     * @ODM\String
     */
    private $title;

    /**
     * @ODM\ReferenceOne(targetDocument="Article", mappedBy="type")
     * @Gedmo\ReferenceIntegrity("nullify")
     * @var Article
     */
    protected $article;

    // ...
}
```

It is necessary to have the 'mappedBy' option set, to be able to access the referenced documents.
On removal of Type, on the referenced Article the Type reference will be nullified (removed)

<a name="yaml-mapping"></a>

## Yaml mapping example:

Yaml mapped Article: **/mapping/yaml/Documents.Article.dcm.yml**

```
---
Document\Type:
  type: document
  collection: types
  fields:
      id:
          id:     true
      title:
          type:   string
      article:
          reference: true
          type: one
          mappedBy: type
          targetDocument: Document\Article
          gedmo:
              referenceIntegrity: nullify   # or restrict

```

It is necessary to have the 'mappedBy' option set, to be able to access the referenced documents.

<a name="advanced-examples"></a>

## Usage examples:

Few operations to see it in action:

``` php
<?php
$article = new Article;
$article->setTitle('My Article');

$type = new Type;
$type->setTitle('Published');

$article = $em->getRepository('Entity\Article')->findByTitle('My Article');
$article->setType($type);

$em->persist($article);
$em->persist($type);
$em->flush();

$type = $em->getRepository('Entity\Type')->findByTitle('Published');
$em->remove($type);
$em->flush();

$article = $em->getRepository('Entity\Article')->findByTitle('My Article');
$article->getType(); // won't be referenced to Type anymore
```

When 'ReferenceIntegrity' is set to 'restrict' a `ReferenceIntegrityStrictException` will be thrown, only when there
is a referenced document.

Easy like that, any suggestions on improvements are very welcome
