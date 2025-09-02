# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased](https://github.com/laravel/wayfinder/compare/v0.1.11...main)

## [v0.1.11](https://github.com/laravel/wayfinder/compare/v0.1.10...v0.1.11) - 2025-09-02

### What's Changed

* Fix TypeScript reserved keyword parameters in array syntax by [@pascalbaljet](https://github.com/pascalbaljet) in https://github.com/laravel/wayfinder/pull/87

### New Contributors

* [@pascalbaljet](https://github.com/pascalbaljet) made their first contribution in https://github.com/laravel/wayfinder/pull/87

**Full Changelog**: https://github.com/laravel/wayfinder/compare/v0.1.10...v0.1.11

## [v0.1.10](https://github.com/laravel/wayfinder/compare/v0.1.9...v0.1.10) - 2025-08-25

### What's Changed

* fix: export named routes for invokable controllers by [@istiak-tridip](https://github.com/istiak-tridip) in https://github.com/laravel/wayfinder/pull/84

**Full Changelog**: https://github.com/laravel/wayfinder/compare/v0.1.9...v0.1.10

## [v0.1.9](https://github.com/laravel/wayfinder/compare/v0.1.8...v0.1.9) - 2025-08-21

### What's Changed

* Add Postgres default int4 type to the number map by [@billypoke](https://github.com/billypoke) in https://github.com/laravel/wayfinder/pull/81
* fix: Add missing "options" to `Method` type by [@r3Fuze](https://github.com/r3Fuze) in https://github.com/laravel/wayfinder/pull/80

### New Contributors

* [@billypoke](https://github.com/billypoke) made their first contribution in https://github.com/laravel/wayfinder/pull/81
* [@r3Fuze](https://github.com/r3Fuze) made their first contribution in https://github.com/laravel/wayfinder/pull/80

**Full Changelog**: https://github.com/laravel/wayfinder/compare/v0.1.8...v0.1.9

## [v0.1.8](https://github.com/laravel/wayfinder/compare/v0.1.7...v0.1.8) - 2025-08-20

### What's Changed

* Fix type errors for URL default methods by [@joetannenbaum](https://github.com/joetannenbaum) in https://github.com/laravel/wayfinder/pull/78
* CI JS code formatting by [@joetannenbaum](https://github.com/joetannenbaum) in https://github.com/laravel/wayfinder/pull/79

**Full Changelog**: https://github.com/laravel/wayfinder/compare/v0.1.7...v0.1.8

## [v0.1.7](https://github.com/laravel/wayfinder/compare/v0.1.6...v0.1.7) - 2025-08-19

### What's Changed

* feat: introduce route utility types for improved DX by [@istiak-tridip](https://github.com/istiak-tridip) in https://github.com/laravel/wayfinder/pull/68
* Add compatibility with strictNullChecks by [@nickwelsh](https://github.com/nickwelsh) in https://github.com/laravel/wayfinder/pull/76
* feat: Adds support for providing default URL parameters via the frontend by [@owenconti](https://github.com/owenconti) in https://github.com/laravel/wayfinder/pull/75
* Update JS packages by [@SuperDJ](https://github.com/SuperDJ) in https://github.com/laravel/wayfinder/pull/73
* Resolve multiple bugs by [@istiak-tridip](https://github.com/istiak-tridip) in https://github.com/laravel/wayfinder/pull/72

### New Contributors

* [@nickwelsh](https://github.com/nickwelsh) made their first contribution in https://github.com/laravel/wayfinder/pull/76
* [@owenconti](https://github.com/owenconti) made their first contribution in https://github.com/laravel/wayfinder/pull/75
* [@SuperDJ](https://github.com/SuperDJ) made their first contribution in https://github.com/laravel/wayfinder/pull/73

**Full Changelog**: https://github.com/laravel/wayfinder/compare/v0.1.6...v0.1.7

## [v0.1.6](https://github.com/laravel/wayfinder/compare/v0.1.5...v0.1.6) - 2025-05-16

### What's Changed

* Wayfinder Vite plugin by [@joetannenbaum](https://github.com/joetannenbaum) in https://github.com/laravel/wayfinder/pull/67
* feat: Add multiple imports handling by [@maicol07](https://github.com/maicol07) in https://github.com/laravel/wayfinder/pull/63

### New Contributors

* [@maicol07](https://github.com/maicol07) made their first contribution in https://github.com/laravel/wayfinder/pull/63

**Full Changelog**: https://github.com/laravel/wayfinder/compare/v0.1.5...v0.1.6

## [v0.1.5](https://github.com/laravel/wayfinder/compare/v0.1.4...v0.1.5) - 2025-05-07

### What's Changed

* Grammar Check of README.md by [@j3ns3n](https://github.com/j3ns3n) in https://github.com/laravel/wayfinder/pull/43
* php 8.2 support by [@emhashef](https://github.com/emhashef) in https://github.com/laravel/wayfinder/pull/42
* Update README.md to improve integration by [@nckrtl](https://github.com/nckrtl) in https://github.com/laravel/wayfinder/pull/35
* Improve docs by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/wayfinder/pull/47
* Add support for generating route while routes are cached by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/wayfinder/pull/45
* Test it can import storage routes by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/wayfinder/pull/46
* Blade + TypeScript clean up by [@joetannenbaum](https://github.com/joetannenbaum) in https://github.com/laravel/wayfinder/pull/52
* Handle namespaced routes correctly by [@joetannenbaum](https://github.com/joetannenbaum) in https://github.com/laravel/wayfinder/pull/51
* Fix: Use forward slashes in docblock paths by [@istiak-tridip](https://github.com/istiak-tridip) in https://github.com/laravel/wayfinder/pull/56
* Fix for PostgreSQL ID typing by [@joetannenbaum](https://github.com/joetannenbaum) in https://github.com/laravel/wayfinder/pull/58
* Don't escape forward slashes in URI by [@joetannenbaum](https://github.com/joetannenbaum) in https://github.com/laravel/wayfinder/pull/60

### Potential Minor Breaking Change

The changes to how Wayfinder writes barrel files in v0.1.3 were largely reverted. Sorry for the back and forth there, still refining the DX.

### New Contributors

* [@j3ns3n](https://github.com/j3ns3n) made their first contribution in https://github.com/laravel/wayfinder/pull/43
* [@emhashef](https://github.com/emhashef) made their first contribution in https://github.com/laravel/wayfinder/pull/42
* [@nckrtl](https://github.com/nckrtl) made their first contribution in https://github.com/laravel/wayfinder/pull/35
* [@istiak-tridip](https://github.com/istiak-tridip) made their first contribution in https://github.com/laravel/wayfinder/pull/56

**Full Changelog**: https://github.com/laravel/wayfinder/compare/v0.1.4...v0.1.5

## [v0.1.4](https://github.com/laravel/wayfinder/compare/v0.1.3...v0.1.4) - 2025-04-07

### What's Changed

* Check possible undefined by [@hosmelq](https://github.com/hosmelq) in https://github.com/laravel/wayfinder/pull/30
* Add route prefix to reserved js keyword route names by [@aktasumut34](https://github.com/aktasumut34) in https://github.com/laravel/wayfinder/pull/32

### New Contributors

* [@aktasumut34](https://github.com/aktasumut34) made their first contribution in https://github.com/laravel/wayfinder/pull/32

**Full Changelog**: https://github.com/laravel/wayfinder/compare/v0.1.3...v0.1.4

## [v0.1.3](https://github.com/laravel/wayfinder/compare/v0.1.1...v0.1.3) - 2025-04-04

### What's Changed

* Check if routable is an Eloquent model ([#8](https://github.com/laravel/wayfinder/pull/8))
* Return literal HTTP methods, fixes Inertia TypeScript error ([14](https://github.com/laravel/wayfinder/pull/14))
* Strip additional whitespace ([#12](https://github.com/laravel/wayfinder/pull/12))
* Fix: Convert Hyphenated File Names to Camel Case ([#13](https://github.com/laravel/wayfinder/pull/13))
* Do not delete directories when skipping by [@hosmelq](https://github.com/hosmelq) in https://github.com/laravel/wayfinder/pull/16
* fix: URL constructor: // is not a valid URL. by [@ArthurYdalgo](https://github.com/ArthurYdalgo) in https://github.com/laravel/wayfinder/pull/20
* docs: update Vite watcher pattern to support nested routes by [@IlyasMohetna](https://github.com/IlyasMohetna) in https://github.com/laravel/wayfinder/pull/19
* Remove trim deadspace directive for function arguments by [@joetannenbaum](https://github.com/joetannenbaum) in https://github.com/laravel/wayfinder/pull/24
* fix: build.ts on Windows by [@Niush](https://github.com/Niush) in https://github.com/laravel/wayfinder/pull/23
* Refactor contribution documentation and add support and security guidelines by [@michaelnabil230](https://github.com/michaelnabil230) in https://github.com/laravel/wayfinder/pull/17
* Expanded list of JavaScript reserved words by [@joetannenbaum](https://github.com/joetannenbaum) in https://github.com/laravel/wayfinder/pull/25
* Fix barrel files by [@joetannenbaum](https://github.com/joetannenbaum) in https://github.com/laravel/wayfinder/pull/26
* Allow chaining of reserved JavaScript words from controllers by [@joetannenbaum](https://github.com/joetannenbaum) in https://github.com/laravel/wayfinder/pull/28
* Fix typo in readme and change highlighting for notes by [@AndrewMast](https://github.com/AndrewMast) in https://github.com/laravel/wayfinder/pull/27

### Potential Minor Breaking Change

The changes to how Wayfinder writes barrel files in https://github.com/laravel/wayfinder/pull/26 might affect your codebase depending on how you are currently importing actions or routes.

### New Contributors

* [@hosmelq](https://github.com/hosmelq) made their first contribution in https://github.com/laravel/wayfinder/pull/16
* [@ArthurYdalgo](https://github.com/ArthurYdalgo) made their first contribution in https://github.com/laravel/wayfinder/pull/20
* [@IlyasMohetna](https://github.com/IlyasMohetna) made their first contribution in https://github.com/laravel/wayfinder/pull/19
* [@Niush](https://github.com/Niush) made their first contribution in https://github.com/laravel/wayfinder/pull/23
* [@AndrewMast](https://github.com/AndrewMast) made their first contribution in https://github.com/laravel/wayfinder/pull/27

**Full Changelog**: https://github.com/laravel/wayfinder/compare/v0.1.2...v0.1.3

## [v0.1.1](https://github.com/laravel/wayfinder/compare/v0.1.0...v0.1.1)

- Fix middleware closure crash ([#5](https://github.com/laravel/wayfinder/pull/5))

## v0.1.0

- Initial release!
