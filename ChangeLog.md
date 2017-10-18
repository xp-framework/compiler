XP Compiler ChangeLog
=====================

## ?.?.? / ????-??-??

## 0.8.0 / 2017-10-18

* Ensured line numbers are kept; this is important for tracing
  warnings, errors and exceptions.
  (@thekid)

## 0.7.0 / 2017-10-15

* Improved function, closure, lambda and method node layouts - @thekid
* Fixed closures not being able to use by reference - @thekid
* Implemented parameter annotations via `$param: inject` - @thekid

## 0.6.0 / 2017-10-15

* Ensured types are checked where natively supported - @thekid
* Implemented capturing locals in lambda expressions - @thekid
* Recorded property types in cached meta data - @thekid
* Implemented support for dynamic new via `new $type`- @thekid
* Fixed assignment operator - @thekid
* Fixed parameter types - @thekid
* Fixed annotations not having access to class scope - @thekid
* Fixed constant emittance in PHP 7.1+ - @thekid
* Fixed trait declaration - @thekid
* Fixed issue #3: Annotations in package - @thekid
* Fixed issue #4: "xp compile" installation - @thekid

## 0.5.0 / 2017-10-15

* Removed unused scope defines - @thekid
* Fixed endless loop for unclosed argument lists - @thekid
* Fixed type annotations not being parsed - @thekid

## 0.4.0 / 2017-10-15

* Optimized runtime performance by including annotations as 
  metadata inside code, see issue #1.
  (@thekid)
* Fixed annotation parsing - @thekid
* Made `xp help compile` display something useful - @thekid
* Fixed compatibility with XP7 console streaming - @thekid

## 0.3.0 / 2017-10-15

* Registered `xp compile` subcommand - @thekid
* Simulated `yield from` in PHP 5.6 in a limited fashion - @thekid
* Added support for nullable types from PHP 7.1 - @thekid
* Implemented short `list(...)` syntax from PHP 7.1 - @thekid
* Added support for anonymous classes from PHP 7.0 - @thekid
* Implemented constant modifiers from PHP 7.1 - @thekid
* Added support for comparison operator `<=>` from PHP 7.0 - @thekid
* Added support for `object` typehint from PHP 7.2 - @thekid

## 0.2.0 / 2017-10-14

* Added factory to retrieve emitter for a given PHP runtime - @thekid
* Extracted PHP version specific handling to dedicated classes - @thekid
* Ensured compiled code can be loaded for annotation parsing - @thekid

## 0.1.0 / 2017-10-14

* First public release - @thekid
