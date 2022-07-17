# Doctrine Extensions Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

Each release should include sub-headers for the Extension above the types of
changes, in order to more easily recognize how an Extension has changed in
a release.

```
## [3.6.1] - 2022-07-26
#### Fixed
- Sortable: Fix issue with add+delete position synchronization (#1932)
```

---

## [Unreleased]

## [3.8.0] - 2022-07-17
#### Added
- Sluggable: Add support for `DateTimeImmutable` fields
- Tree: [NestedSet] `childrenQueryBuilder()` to allow specifying sort order separately for each field
- Tree: [NestedSet] Added option to reorder only direct children in `reorder()` method

## Changed
- Tree: In `ClosureTreeRepository::removeFromTree()` and `NestedTreeRepository::removeFromTree()` when something fails in the transaction, it uses the `code` from the original exception to construct the `\Gedmo\Exception\RuntimeException` instance instead of `null`.

#### Fixed
- Sluggable: Cast slug to string before passing it as argument 2 to `preg_match()` (#2473)
- Sortable: [SortableGroup] Fix sorting date columns in SQLite (#2462).
- PHPDoc of `AbstractMaterializedPath::removeNode()` and `AbstractMaterializedPath::getChildren()`
- Retrieving the proper metadata cache from Doctrine when using a CacheWarmer.

## [3.7.0] - 2022-05-17
## Added
- Add support for doctrine/persistence 3

## Changed
- Removed call to deprecated `ClassMetadataFactory::getCacheDriver()` method.
- Dropped support for doctrine/mongodb-odm < 2.3.
- Make doctrine/cache an optional dependency.

## Fixed
- Loggable: Fix `appendNumber` renaming for files without extension (#2228)

## [3.6.0] - 2022-03-19
### Added
- Translatable: Add defaultTranslationValue option to allow null or string value (#2167). TranslatableListener can hydrate object properties with null value, but it may cause a Type error for non-nullable getter upon a missing translation.

### Fixed
- Uploadable: `FileInfoInterface::getSize()` return type declaration (#2413).
- Tree: Setting a new Tree Root when Tree Parent is `null`.
- Tree: update cache key used by Closure to match Doctrine's one (#2416).
- Tree: persist order does not affect entities on Closure (#2432)

## [3.5.0] - 2022-01-10
### Added
- SoftDeleteable: Support to use annotations as attributes on PHP >= 8.0.
- Blameable: Support to use annotations as attributes on PHP >= 8.0.
- IpTraceable: Support to use annotations as attributes on PHP >= 8.0.
- Sortable: Support to use annotations as attributes on PHP >= 8.0.
- Sluggable: Support to use annotations as attributes on PHP >= 8.0.
- Uploadable: Support to use annotations as attributes on PHP >= 8.0.
- Tree: Support to use annotations as attributes on PHP >= 8.0.
- References: Support to use annotations as attributes on PHP >= 8.0.
- ReferenceIntegrity: Support to use annotations as attributes on PHP >= 8.0.
- SoftDeleteable: Support for custom column types (like Carbon).
- Timestampable: Support for custom column types (like Carbon).
- Translatable: Added an index to `Translation` entity to speed up searches using 
  `Gedmo\Translatable\Entity\Repository\TranslationRepository::findTranslations()` method. 
- `Gedmo\Mapping\Event\AdapterInterface::getObject()` method.

### Fixed
- Blameable, IpTraceable, Timestampable: Type handling for the tracked field values configured in the origin field.
- Loggable: Using only PHP 8 attributes.
- References: Avoid deprecations using LazyCollection with PHP 8.1
- Tree: Association mapping problems using Closure tree strategy (by manually defining mapping on the closure entity).
- Wrong PHPDoc type declarations.
- Avoid calling deprecated `AbstractClassMetadataFactory::getCacheDriver()` method.
- Avoid deprecations using `doctrine/mongodb-odm` >= 2.2
- Translatable: `Gedmo\Translatable\Document\Repository\TranslationRepository::findObjectByTranslatedField()`
  method accessing a non-existing key.

### Deprecated
- Tree: When using Closure tree strategy, it is deprecated not defining the mapping associations of the closure entity.
- `Gedmo\Tool\Logging\DBAL\QueryAnalizer` class without replacement.
- Using YAML mapping is deprecated, you SHOULD migrate to attributes, annotations or XML.
- `Gedmo\Mapping\Event\AdapterInterface::__call()` method.
- `Gedmo\Tool\Wrapper\AbstractWrapper::clear()` method.
- `Gedmo\Tool\Wrapper\WrapperInterface::populate()` method.

### Changed
- In order to use a custom cache for storing configuration of an extension, the user has to call `setCacheItemPool()`
  on the extension listener passing an instance of `Psr\Cache\CacheItemPoolInterface`.

## [3.4.0] - 2021-12-05
### Added
- PHP 8 Attributes support for Doctrine MongoDB to document & traits.
- Support for doctrine/dbal >=3.2.
- Timestampable: Support to use annotations as attributes on PHP >= 8.0.
- Loggable: Support to use annotations as attributes on PHP >= 8.0.

### Changed
- Translatable: Dropped support for other values than "true", "false", "1" and "0" in the `fallback` attribute of the `translatable`
  element in the XML mapping.
- Tree: Dropped support for other values than "true", "false", "1" and "0" in the `activate-locking` attribute of the `tree`
  element in the XML mapping.
- Tree: Dropped support for other values than "true", "false", "1" and "0" in the `append_id`, `starts_with_separator` and
  `ends_with_separator` attributes of the `tree-path` element in the XML mapping.
- Dropped support for doctrine/dbal < 2.13.1.
- The third argument of `Gedmo\SoftDeleteable\Query\TreeWalker\Exec\MultiTableDeleteExecutor::__construct()` requires a `Doctrine\ORM\Mapping\ClassMetadata` instance.

## [3.3.1] - 2021-11-18
### Fixed
- Translatable: Using ORM/ODM attribute mapping and translatable annotations.
- Tree: Missing support for `tree-path-hash` fields in XML mapping.
- Tree: Check for affected rows at `ClosureTreeRepository::cleanUpClosure()` and `Closure::updateNode()`.
- `Gedmo\Mapping\Driver\Xml::_loadMappingFile()` behavior in scenarios where `libxml_disable_entity_loader(true)` was previously
  called.
- Loggable: Missing support for `versioned` fields at `attribute-override` in XML mapping.

## [3.3.0] - 2021-11-15
### Added
- Support to use Translatable annotations as attributes on PHP >= 8.0.

### Deprecated
- `Gedmo\Mapping\Driver\File::$_paths` property and `Gedmo\Mapping\Driver\File::setPaths()` method are deprecated and will
  be removed in version 4.0, as they are not used.

### Fixed
- Value passed in the `--config` option to `fix-cs` Composer script.
- Return value for `replaceRelative()` and `replaceInverseRelative()` at `Gedmo\Sluggable\Mapping\Event\Adapter\ODM` if the
  query result does not implement `Doctrine\ODM\MongoDB\Iterator\Iterator`.
- Restored compatibility with doctrine/orm >= 2.10.2 (#2272).
  Since doctrine/orm 2.10, `Doctrine\ORM\UnitOfWork` relies on SPL object IDs instead of hashes, thus we need to adapt our codebase in order to be compatible with this change.
  As `Doctrine\ODM\MongoDB\UnitOfWork` from doctrine/mongodb-odm still uses `spl_object_hash()`, all `spl_object_hash()` calls were replaced by `spl_object_id()` to make it work with both ORM and ODM managers.

## [3.2.0] - 2021-10-05
### Added
- PHP 8 Attributes for Doctrine ORM to entities & traits (#2251) 

### Fixed
- Removed legacy checks targeting older versions of PHP (#2201)
- Added missing XSD definitions (#2244)
- Replaced undefined constants from `Doctrine\DBAL\Types\Type` at `Gedmo\Translatable\Mapping\Event\Adapter\ORM::foreignKey()` (#2250)
- Add conflict against "doctrine/orm" >=2.10 in order to guarantee the schema extension (see https://github.com/doctrine/orm/pull/8852) (#2255)

## [3.1.0] - 2021-06-22
### Fixed
- Allow installing doctrine/cache 2.0 (thanks @alcaeus!)
- Make doctrine/cache a dev dependency

## [3.0.5] - 2021-04-23
### Fixed
- Use path_separator when removing children (#2217)

## [3.0.4] - 2021-03-27
### Fixed
- Add hacky measure to resolve incompatibility with DoctrineBundle 2.3 [#2211](https://github.com/doctrine-extensions/DoctrineExtensions/pull/2211)

## [3.0.3] - 2021-01-23
### Fixed
- Add PHP 8 compatibility to `composer.json`, resolving minor function parameter deprecations [#2194](https://github.com/Atlantic18/DoctrineExtensions/pull/2194)

## [3.0.2] - 2021-01-23
- Ignore; tag & version mismatch

## [3.0.1] - 2021-01-23
- Ignore; wrong branch published

## [3.0.0] - 2020-09-23
### Notable & Breaking Changes
- Minimum PHP version requirement of 7.2
- Source files moved from `/lib/Gedmo` to `/src`
- Added compatibility for `doctrine/common` 3.0 and `doctrine/persistence` 2.0
- All string column type annotations changed to 191 character length (#1941)
- Removed support for `\Zend_date` date format [#2163](https://github.com/Atlantic18/DoctrineExtensions/pull/2163)
- Renamed `Zend Framework` to `Laminas` [#2163](https://github.com/Atlantic18/DoctrineExtensions/pull/2163)

Changes below marked ⚠️ may also be breaking, if you have extended DoctrineExtensions.

### MongoDB
- Requires the `ext-mongodb` PHP extension. Usage of `ext-mongo` is deprecated and will be removed in the next major version.
- Minimum Doctrine MongoDB ODM requirement of 2.0
- Usages of `\MongoDate` replaced with `MongoDB\BSON\UTCDateTime`

### Global / Shared
#### Fixed
- Removed `null` parameter from `Doctrine\Common\Cache\Cache::save()` calls (#1996)

### Tree
#### Fixed
- The value of path source property is cast to string type for Materialized Path Tree strategy (#2061)

### SoftDeleteable
#### Changed
- ⚠️ Generate different Date values based on column type (#2115)
