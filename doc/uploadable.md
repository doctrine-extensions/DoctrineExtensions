# Uploadable behavior extension for Doctrine 2

**Uploadable** behavior provides the tools to manage the persistance of files with
Doctrine 2, including automatic handling of moving, renaming and removal of files and other features.

Features:

- Extension moves, removes and renames files according to configuration automatically
- Lots of options: Allow overwrite, append a number if file exists, filename generators, post-move callbacks, etc.
- It can be extended to work not only with uploaded files, but with files coming from any source (an URL, another
 file in the same server, etc).

Content:

- [Including](#including-extension) the extension
- Entity [example](#entity-mapping)
- [Yaml](#yaml-mapping) mapping example
- [Xml](#xml-mapping) mapping example
- Usage [examples](#usage)

<a name="including-extension"></a>

## Setup and autoloading

Read the [documentation](http://github.com/l3pp4rd/DoctrineExtensions/blob/master/doc/annotations.md#em-setup)
or check the [example code](http://github.com/l3pp4rd/DoctrineExtensions/tree/master/example)
on how to setup and use the extensions in most optimized way.


<a name="entity-mapping"></a>

## Uploadable Entity example:

### Uploadable annotations:
1. **@Gedmo\Mapping\Annotation\Uploadable** this class annotation tells if a class is Uploadable. Available configuration options:
 1.**allowOverwrite** - If this option is true, it will overwrite a file if it already exists. If you set "false", an
 exception will be thrown. Default: false
 2. **appendNumber** - If this option is true and "allowOverwrite" is false, in the case that the file already exists,
 it will append a number to the filename. Example: if you're uploading a file named "test.txt", if the file already
 exists and this option is true, the extension will modify the name of the uploaded file to "test-1.txt", where "1"
 could be any number. The extension will check if the file exists until it finds a filename with a number as its postfix that is not used.
 If you use a filename generator and this option is true, it will append a number to the filename anyway if a file with
 the same name already exists.
 Default value: false
 3. **path** - This option expects a string containing the path where the files represented by this entity will be moved.
Default: "". Path can be set in other ways: From the listener or from a method. More details later.
 4. **pathMethod** - Similar to option "path", but this time it represents the name of a method on the entity that
 will return the path to which the files represented by this entity will be moved. This is useful in several cases.
 For example, you can set specific paths for specific entities, or you can get the path from other sources (like a
 framework configuration) instead of hardcoding it in the entity. Default: ""
 5. **callback** - This option allows you to set a method name. If this option is set, the method will be called after
 the file is moved. Default value: "". As first argument, this method can receive an array with information about the uploaded file, which
 includes the following keys:
   1. **fileName**: The filename.
   2. **fileExtension**: The extension of the file (including the dot). Example: .jpg
   3. **fileWithoutExt**: The filename without the extension.
   4. **filePath**: The file path. Example: /my/path/filename.jpg
   5. **fileMimeType**: The mime-type of the file. Example: text/plain.
   6. **fileSize**: Size of the file in bytes. Example: 140000.
 6. **filenameGenerator**: This option allows you to set a filename generator for the file. There are two already included
 by the extension: **SHA1**, which generates a sha1 filename for the file, and **ALPHANUMERIC**, which "normalizes"
 the filename, leaving only alphanumeric characters in the filename, and replacing anything else with a "-". You can
 even create your own FileGenerator class (implementing the FileGeneratorInterface) and set this option with the
 fully qualified class name. The other option available is "NONE" which, as you may guess, means no generation for the
 filename will occur. Default: "NONE".
2. **@Gedmo\Mapping\Annotation\UploadableFilePath**: This annotation is used to set which field will receive the path
 to the file. The field MUST be of type "string" and this annotation is REQUIRED.
3. **@Gedmo\Mapping\Annotation\UploadableFileMimeType**: This is an optional annotation used to set which field will
 receive the mime type of the file as its value. This field MUST be of type "string".
4. **@Gedmo\Mapping\Annotation\UploadableFileSize**: This is an optional annotation used to set which field will
 receive the size in bytes of the file as its value. This field MUST be of type "decimal".

### Notes about setting the path where the files will be moved:

You have three choices to configure the path. You can set a default path on the listener, which will be used on every
entity which doesn't have a path or pathMethod defined:

``` php
$listener->setDefaultPath('/my/path');
```

You can use the Uploadable "path" option to set the path:

``` php
/**
 * @ORM\Entity
 * @Gedmo\SoftDeleteable(path="/my/path")
 */
class File
{
    //...
}
```

Or you can use the Uploadable "pathMethod" option to set the name of the method which will return the path:

``` php
/**
 * @ORM\Entity
 * @Gedmo\SoftDeleteable(pathMethod="getPath")
 */
class File
{
    public function getPath()
    {
        return '/my/path';
    }
}
```


### Note regarding the Uploadable interface:

The Uploadable interface is not necessary, except in cases there
you need to identify an entity as Uploadable. The metadata is loaded only once then
you need to identify an entity as Uploadable. The metadata is loaded only once then
cache is activated

### Minimum configuration needed:

``` php
<?php
namespace Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

// If you don't set the path here, remember that you must set it on the listener!

/**
 * @ORM\Entity
 * @Gedmo\Uploadable
 */
class File
{
    // Other fields..

    /**
     * @ORM\Column(name="path", type="string")
     * @Gedmo\UploadableFilePath
     */
    private $path;
}
```

### Example of an entity with all the configurations set:

``` php
<?php
namespace Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @Gedmo\Uploadable(path="/my/path", callback="myCallbackMethod", filenameGenerator="SHA1", allowOverwrite=true, appendNumber=true)
 */
class File
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\Column(name="path", type="string")
     * @Gedmo\UploadableFilePath
     */
    private $path;

    /**
     * @ORM\Column(name="mime_type", type="string")
     * @Gedmo\UploadableFileMimeType
     */
    private $mimeType;

    /**
     * @ORM\Column(name="size", type="decimal")
     * @Gedmo\UploadableFileSize
     */
    private $size;


    public function myCallbackMethod(array $info)
    {
        // Do some stuff with the file..
    }

    // Other methods..
}
```


<a name="yaml-mapping"></a>

## Yaml mapping example:

Yaml mapped Article: **/mapping/yaml/Entity.Article.dcm.yml**

```
---
Entity\File:
  type: entity
  table: files
  gedmo:
    uploadable:
      allowOverwrite: true
      appendNumber: true
      path: '/my/path'
      pathMethod: getPath
      callback: callbackMethod
      filenameGenerator: SHA1
  id:
    id:
      type: integer
      generator:
        strategy: AUTO
  fields:
    path:
      type: string
      gedmo:
        - uploadableFilePath
    mimeType:
      type: string
      gedmo:
        - uploadableFileMimeType
    size:
      type: decimal
      gedmo:
        - uploadableFileSize
```

<a name="xml-mapping"></a>

## Xml mapping example

``` xml
<?xml version="1.0" encoding="UTF-8"?>

<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
                  xmlns:gedmo="http://gediminasm.org/schemas/orm/doctrine-extensions-mapping">

    <entity name="Entity\File" table="files">

        <id name="id" type="integer" column="id">
            <generator strategy="AUTO"/>
        </id>

        <field name="mimeType" column="mime" type="string">
            <gedmo:uploadable-file-mime-type />
        </field>

        <field name="size" column="size" type="decimal">
            <gedmo:uploadable-file-size />
        </field>

        <field name="path" column="path" type="string">
            <gedmo:uploadable-file-path />
        </field>

        <gedmo:uploadable
            allow-overwrite="true"
            append-number="true"
            path="/my/path"
            path-method="getPath"
            callback="callbackMethod"
            filename-generator="SHA1" />

    </entity>

</doctrine-mapping>
```

<a name="usage"></a>

## Usage:

``` php
<?php
// Example setting the path directly on the listener:

$listener->setDefaultPath('/my/app/web/upload');

if (isset($_FILES['images']) && is_array($_FILES['images']) {
    foreach ($_FILES['images'] as $fileInfo) {
        $file = new File();

        $listener->setEntityFileInfo($file, $fileInfo);

        // You can set the file info directly with a FileInfoInterface object, like this:
        //
        // $listener->setEntityFileInfo($file, new FileInfoArray($fileInfo));
        //
        // Or create your own class which implements FileInfoInterface
        //
        // $listener->setEntityFileInfo($file, new MyOwnFileInfo($fileInfo));


        $em->persist($file);
    }
}

$em->flush();
```

Easy like that, any suggestions on improvements are very welcome.