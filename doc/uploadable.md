# Uploadable behavior extension for Doctrine

**Uploadable** behavior provides the tools to manage the persistence of files with
Doctrine, including automatic handling of moving, renaming and removal of files and other features.

Features:

- Extension moves, removes and renames files according to configuration automatically
- Lots of options: Allow overwrite, append a number if file exists, filename generators, post-move callbacks, etc.
- It can be extended to work not only with uploaded files, but with files coming from any source (an URL, another
 file in the same server, etc).
- Validation of size and mime type

Content:

- [Including](#including-extension) the extension
- Entity [example](#entity-mapping)
- [Xml](#xml-mapping) mapping example
- Usage [examples](#usage)
- [Using](#additional-usages) the extension to handle not only uploaded files
- [Custom](#custom-mime-type-guessers) mime type guessers

<a name="including-extension"></a>

## Setup and autoloading

Read the [documentation](./annotations.md#em-setup)
or check the [example code](../example)
on how to setup and use the extensions in most optimized way.


<a name="entity-mapping"></a>

## Uploadable Entity example:

### Uploadable annotations and attributes:

These classes can be used either as annotation or as attribute:

1. **@Gedmo\Mapping\Annotation\Uploadable** this class annotation/attribute tells if a class is Uploadable. Available configuration options:
    * **allowOverwrite** - If this option is true, it will overwrite a file if it already exists. If you set "false", an
    exception will be thrown. Default: false
    * **appendNumber** - If this option is true and "allowOverwrite" is false, in the case that the file already exists,
    it will append a number to the filename. Example: if you're uploading a file named "test.txt", if the file already
    exists and this option is true, the extension will modify the name of the uploaded file to "test-1.txt", where "1"
    could be any number. The extension will check if the file exists until it finds a filename with a number as its postfix that is not used.
    If you use a filename generator and this option is true, it will append a number to the filename anyway if a file with
    the same name already exists.
    Default value: false
    * **path** - This option expects a string containing the path where the files represented by this entity will be moved.
    Default: "". Path can be set in other ways: From the listener or from a method. More details later.
    * **pathMethod** - Similar to option "path", but this time it represents the name of a method on the entity that
    will return the path to which the files represented by this entity will be moved. This is useful in several cases.
    For example, you can set specific paths for specific entities, or you can get the path from other sources (like a
    framework configuration) instead of hardcoding it in the entity. Default: "". As first argument this method takes
    default path, so you can return path relative to default.
    * **callback** - This option allows you to set a method name. If this option is set, the method will be called after
    the file is moved. Default value: "". As first argument, this method can receive an array with information about the uploaded file, which
    includes the following keys:
        1. **fileName**: The filename.
        2. **fileExtension**: The extension of the file (including the dot). Example: .jpg
        3. **fileWithoutExt**: The filename without the extension.
        4. **filePath**: The file path. Example: /my/path/filename.jpg
        5. **fileMimeType**: The mime-type of the file. Example: text/plain.
        6. **fileSize**: Size of the file in bytes. Example: 140000.
    * **filenameGenerator**: This option allows you to set a filename generator for the file. There are two already included
    by the extension: **SHA1**, which generates a sha1 filename for the file, and **ALPHANUMERIC**, which "normalizes"
    the filename, leaving only alphanumeric characters in the filename, and replacing anything else with a "-". You can
    even create your own FilenameGenerator class (implementing the Gedmo\Uploadable\FilenameGenerator\FilenameGeneratorInterface) and set this option with the
    fully qualified class name. The other option available is "NONE" which, as you may guess, means no generation for the
    filename will occur. Default: "NONE".
    * **maxSize**: This option allows you to set a maximum size for the file in bytes. If file size exceeds the value
    set in this configuration, an exception of type "UploadableMaxSizeException" will be thrown. By default, its value is set to 0, meaning
    that no size validation will occur.
    * **allowedTypes**: With this option you can set a comma-separated list of allowed mime types for the file. The extension
    will use a simple mime type guesser to guess the file type, and then it will compare it to the list of allowed types.
    If the mime type is not valid, then an exception of type "UploadableInvalidMimeTypeException" will be thrown. If you
    set this option, you can't set the **disallowedTypes** option described next. By default, no validation of mime type
    occurs. If you want to use a custom mime type guesser, see [this](#custom-mime-type-guessers).
    * **disallowedTypes**: Similar to the option **allowedTypes**, but with this one you configure a "black list" of
    mime types. If the mime type of the file is on this list, n exception of type "UploadableInvalidMimeTypeException" will be thrown. If you
    set this option, you can't set the **allowedTypes** option described above. By default, no validation of mime type
    occurs. If you want to use a custom mime type guesser, see [this](#custom-mime-type-guessers).
2. **@Gedmo\Mapping\Annotation\UploadableFilePath**: This annotation/attribute is used to set which field will receive the path
 to the file. The field MUST be of type "string". Either this one or UploadableFileName annotation/attribute is REQUIRED to be set.
3. **@Gedmo\Mapping\Annotation\UploadableFileName**: This annotation/attribute is used to set which field will receive the name
 of the file. The field MUST be of type "string". Either this one or UploadableFilePath annotation/attribute is REQUIRED to be set.
4. **@Gedmo\Mapping\Annotation\UploadableFileMimeType**: This is an optional annotation/attribute used to set which field will
 receive the mime type of the file as its value. This field MUST be of type "string".
5. **@Gedmo\Mapping\Annotation\UploadableFileSize**: This is an optional annotation/attribute used to set which field will
 receive the size in bytes of the file as its value. This field MUST be of type "decimal".

**Note:** the examples shown here are using annotations and attributes for mapping, you should use
one of them, not both.

### Notes about setting the path where the files will be moved:

You have three choices to configure the path. You can set a default path on the listener, which will be used on every
entity which doesn't have a path or pathMethod defined:

```php
$listener->setDefaultPath('/my/path');
```

You can use the Uploadable "path" option to set the path:

```php
/**
 * @ORM\Entity
 * @Gedmo\Uploadable(path="/my/path")
 */
#[ORM\Entity]
#[Gedmo\Uploadable(path: '/my/path')]
class File
{
    //...
}
```

Or you can use the Uploadable "pathMethod" option to set the name of the method which will return the path:

```php
/**
 * @ORM\Entity
 * @Gedmo\Uploadable(pathMethod="getPath")
 */
#[ORM\Entity]
#[Gedmo\Uploadable(pathMethod: 'getPath')]
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

```php
<?php

namespace Entity;

use Doctrine\DBAL\Types\Types;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

// If you don't set the path here, remember that you must set it on the listener!

/**
 * @ORM\Entity
 * @Gedmo\Uploadable
 */
#[ORM\Entity]
#[Gedmo\Uploadable]
class File
{
    // Other fields..

    /**
     * @ORM\Column(name="path", type="string")
     * @Gedmo\UploadableFilePath
     */
    #[ORM\Column(name: 'path', type: Types::STRING)]
    #[Gedmo\UploadableFilePath]
    private $path;
}
```

### Example of an entity with all the configurations set:

```php
<?php
namespace Entity;

use Doctrine\DBAL\Types\Types;use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Uploadable\Mapping\Validator;

/**
 * @ORM\Entity
 * @Gedmo\Uploadable(path="/my/path", callback="myCallbackMethod", filenameGenerator="SHA1", allowOverwrite=true, appendNumber=true)
 */
#[ORM\Entity]
#[Gedmo\Uploadable(
    path: '/my/path',
    callback: 'myCallbackMethod',
    filenameGenerator: Validator::FILENAME_GENERATOR_SHA1,
    allowOverwrite: true,
    appendNumber: true
)]
class File
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(type="integer")
     */
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private $id;

    /**
     * @ORM\Column(name="path", type="string")
     * @Gedmo\UploadableFilePath
     */
    #[ORM\Column(name: 'path', type: Types::STRING)]
    #[Gedmo\UploadableFilePath]
    private $path;

    /**
     * @ORM\Column(name="name", type="string")
     * @Gedmo\UploadableFileName
     */
    #[ORM\Column(name: 'name', type: Types::STRING)]
    #[Gedmo\UploadableFileName]
    private $name;

    /**
     * @ORM\Column(name="mime_type", type="string")
     * @Gedmo\UploadableFileMimeType
     */
    #[ORM\Column(name: 'mime_type', type: Types::STRING)]
    #[Gedmo\UploadableFileMimeType]
    private $mimeType;

    /**
     * @ORM\Column(name="size", type="decimal")
     * @Gedmo\UploadableFileSize
     */
    #[ORM\Column(name: 'size', type: Types::DECIMAL)]
    #[Gedmo\UploadableFileSize]
    private $size;


    public function myCallbackMethod(array $info)
    {
        // Do some stuff with the file..
    }

    // Other methods..
}
```

<a name="xml-mapping"></a>

## Xml mapping example

```xml
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

        <field name="name" column="name" type="string">
            <gedmo:uploadable-file-name />
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

```php
<?php
// Example setting the path directly on the listener:

$listener->setDefaultPath('/my/app/web/upload');

if (isset($_FILES['images']) && is_array($_FILES['images'])) {
    foreach ($_FILES['images'] as $fileInfo) {
        $file = new File();

        $listener->addEntityFileInfo($file, $fileInfo);

        // You can set the file info directly with a FileInfoInterface object, like this:
        //
        // $listener->addEntityFileInfo($file, new FileInfoArray($fileInfo));
        //
        // Or create your own class which implements FileInfoInterface
        //
        // $listener->addEntityFileInfo($file, new MyOwnFileInfo($fileInfo));


        $em->persist($file);
    }
}

$em->flush();
```

Easy like that, any suggestions on improvements are very welcome.

<a name="additional-usages"></a>

### Using the extension to handle not only uploaded files

Maybe you want to handle files obtained from an URL, or even files that are already located in the same server than your app.
This can be handled in a very simple way. First, you need to create a class that implements the FileInfoInterface
interface. As an example:

```php
use Gedmo\Uploadable\FileInfo\FileInfoInterface;

class CustomFileInfo implements FileInfoInterface
{
    protected $path;
    protected $name;
    protected $size;
    protected $type;
    protected $filename;
    protected $error = 0;

    public function __construct($path)
    {
        $this->path = $path;

        // Now, process the file and fill the rest of the properties.
    }

    // This returns the actual path of the file
    public function getTmpName()
    {
        return $this->path;
    }

    // This returns the filename
    public function getName()
    {
        return $this->name;
    }

    // This returns the file size in bytes
    public function getSize()
    {
        return $this->size;
    }

    // This returns the mime type
    public function getType()
    {
        return $this->type;
    }

    public function getError()
    {
        // This should return 0, as it's only used to return the codes from PHP file upload errors.
        return $this->error;
    }

    // If this method returns true, it will produce that the extension uses "move_uploaded_file" function to move
    // the file. If it returns false, the extension will use the "copy" function.
    public function isUploadedFile()
    {
        return false;
    }
}
```

Or you could simply extend the FileInfoArray class and do the following:

```php
use Gedmo\Uploadable\FileInfo\FileInfoArray;

class CustomFileInfo extends FileInfoArray
{
    public function __construct($path)
    {
        // There's already a $fileInfo property, which needs to be an array with the
        // following keys: tmp_name, name, size, type, error
        $this->fileInfo = array(
            'tmp_name'      => '',
            'name'          => '',
            'size'          => 0,
            'type'          => '',
            'error'         => 0
        );

        // Now process the file at $path and fill the keys with the correct values.
        //
        // In this example we use a $path as the first argument, but it could be an URL
        // to the file we need to obtain, etc.
    }

    public function isUploadedFile()
    {
        // Remember to set this to false so we use "copy" instead of "move_uploaded_file"

        return false;
    }
}
```

And that's it. Then, instead of getting the file info from the $_FILES array, you would do:

```php
// We set the default path in the listener again
$listener->setDefaultPath('/my/path');

$file = new File();

$listener->addEntityFileInfo($file, new CustomFileInfo('/path/to/file.txt'));

$em->persist($file);
$em->flush();
```

<a name="custom-mime-type-guessers"></a>

### Custom Mime type guessers

If you want to use your own mime type guesser, you need to implement the interface "Gedmo\Uploadable\MimeType\MimeTypeGuesserInterface",
which has only one method: "guess($filePath)". Then, you can set the mime type guesser used on the listener in the following
way:

```php
$listener->setMimeTypeGuesser(new MyCustomMimeTypeGuesser());

```
