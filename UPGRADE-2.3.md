# UPGRADE FROM 2.1 or 2.2 TO 2.3

## Translatable

- **TranslationListener** classname has changed into **TranslatableListener**
- Abstract classes (mapped superclasses) were moved into MappedSuperclass subdirectory. Etc.:
**Gedmo\Translatable\Entity\AbstractTranslation** now is **Gedmo\Translatable\Entity\MappedSuperclass\AbstractTranslation**
same for abstract log entries and closure.
