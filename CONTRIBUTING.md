# Contributing to Doctrine Extensions

Thank you for your interest in contributing to Doctrine Extensions!

## Release Cycle

Because Doctrine Extensions is maintained as a single repository, we do not
want to push major and minor releases when only one or two extensions have
been updated. As such, major and minor releases may happen less frequently,
in order to allow all extensions the opportunity to be included in a release.

The Doctrine Extensions team is currently working to determine
a well-defined release cycle for the future. Stay tuned!

## Pull Request Titles

Please include the name(s) of the related extensions as a "tag" in the
pull request title.

> [Tree] Add a new Oak Tree branching style

## Branching

Pull requests should be made to one of the following branches, depending on
the nature of your change(s):

* `2.4.x` - The current stable version branch  
  PRs accepted: bug fixes and patches
* `2.5` - The next minor release  
  PRs accepted: new features and other non-breaking changes
* `master` - The next major release  
  PRs accepted: major new features and breaking changes
  
## Changelog

All updates must include an entry in the [Changelog](/changelog.md).
Put your entry in the `[Unreleased]` section at the top, under the
corresponding Extension and Category.

If there is a related GitHub issue, add it as a suffix to your change.

```
## [Unreleased]
### Loggable
#### Fixed
- Allow emoji in the docs (#123)
```

## What You Can Contribute

Want to contribute but aren't sure where to start? Check out our
[Issue Board](https://github.com/Atlantic18/DoctrineExtensions/issues)!
There are lots of opportunities for helping other users with their issue,
or contributing a reported bug fix or feature request.
