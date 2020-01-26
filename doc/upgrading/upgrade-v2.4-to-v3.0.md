# Upgrading Doctrine Extensions: from v2.4.x to v3.0

Doctrine Extensions v3.0 is primarily focused on upgrading toolsets and dependencies,
to make future work easier and more compatible with modern PHP versions.

Most users will not need significant development time and effort to upgrade to v3.0.

### Known Issues

#### Doctrine MongoDB ODM 2.0 Mapping Drivers

ODM 2.0 made significant changes to parts of their mappers. The YAML driver was removed completely, and the
[XML driver added schema validation](https://github.com/Atlantic18/DoctrineExtensions/issues/2055) that does
not allow mixing of native ODM and Extensions elements.

If you do not use MongoDB ODM at all, or if you use Annotations or PHP mapping drivers, you should be unaffected.
YAML and XML mapping users may not be able to use Doctrine Extensions 3.0, which does not attempt to resolve
these issues at the time. 

### PHP 7.2 Required

PHP 7.1 is no longer maintained as of December 2019.
