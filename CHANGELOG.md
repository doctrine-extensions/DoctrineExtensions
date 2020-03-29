# Doctrine Extensions Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

Each release should include sub-headers for the Extension above the types of
changes, in order to more easily recognize how an Extension has changed in
a release.

```
## [2.4.36] - 2018-07-26
### Sortable
#### Fixed
- Fix issue with add+delete position synchronization (#1932)
```

---

## [Unreleased]
### Notable & Breaking Changes
- Minimum PHP version requirement of 7.2
- Source files moved from `/lib/Gedmo` to `/src`
- All string column type annotations changed to 191 character length (#1941)

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
