# Doctrine2 behavioral extensions

### About this fork
I have added some features to the uploadable extensions.

**Attention**

- The changes were made in a minimum of time. The aim was to adjust the lib to my needs.
- BC for uploadable is broken. You have to update your annotations and calls to UploadableListener::addEntityFileInfo()
- Feel free to contribute

### Changes
#### Multiple file upload on a single entity** #961
This feature breaks BC. Maybe there is somewhere out there having time for fixing that.
 
**Usage**

1. ) Entity Configuration  
All needed annotations are now on the class level. There is a new annotation "Uploadables" which accepts an array of objects of type "Uploadable".
There is no need annotating the property itself.  
```php
/**
 * @ORM\Entity(repositoryClass="Gedmo\Sortable\Entity\Repository\SortableRepository")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\Table(name="frame")
 * @Gedmo\Uploadables(uploadables = {
 *     @Gedmo\Uploadable(
 *         path = "data/uploads", 
 *         filenameGenerator = "SHA1", 
 *         allowOverwrite = true, 
 *         appendNumber = true, 
 *         allowedTypes = "image/jpeg,image/png,image/gif,video/mp4",
 *         filePathProperty = "backgroundVisual"
 *     ),
 *     @Gedmo\Uploadable(
 *         path = "data/uploads", 
 *         filenameGenerator = "SHA1", 
 *         allowOverwrite = true, 
 *         appendNumber = true, 
 *         allowedTypes = "audio/mpeg3",
 *         filePathProperty = "backgroundAudio"
 *     )
 * })
 */
class Frame
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $frameId;
    
    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $backgroundVisual;
    
    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $backgroundAudio;
    
    ...
}
```

2. ) Update calls to UploadableListener::addEntityFileInfo($entity, $fileInfo);  
You have to tell UploadableListener which property of the entity should get updated.
The name of the property is your filePathProperty (in the example above it would be backgroundVisual or backgroundAudio). If you are just using fileNameProperty use the name of that property instead.   
```php
UploadableListener::addEntityFileInfo($entity, $property, $fileInfo);

// in your controller
$uploadManager->addEntityFileInfo($entity, 'backgroundVisual', $fileInfo);

```

#### Deletion of single Files
You can now tell UploadableListener to delete a file from an entity. Therefore I have created a new class for FileInfo.
```php
$fileDelete = new \Gedmo\Uploadable\FileInfo\FileDelete();
UploadableListener::addEntityFileInfo($entity, $property, $fileDelete); // this will delete the file from the disk and set the property to null
```