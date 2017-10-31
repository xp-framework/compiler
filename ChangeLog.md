XP Compiler ChangeLog
=====================

## ?.?.? / ????-??-??

* Fixed issue #16: Enums broken - @thekid

## 1.1.2 / 2017-10-31

* Fixed issue #15: Interop with xp-forge/partial broken - @thekid

## 1.1.1 / 2017-10-31

* Fixed map initialization with keys consisting of complex expressions
  (@thekid)

## 1.1.0 / 2017-10-31

* Implemented trait usage, including aliasing via `as`. See issue #14
  (@thekid)

## 1.0.0 / 2017-10-25

* Indexed type members by name; implementing feature suggested in #10 
  (@thekid)
* **Heads up:** Implemented syntax for parameter annotations as stated 
  in issue #1 - alongside the parameter; no longer in its "targeted" form
  `$param: inject` as in https://github.com/xp-framework/rfc/issues/218
  (@thekid)
* Added support for keywords as methods in PHP 5.6 - @thekid
* Implemented xp-framework/rfc#326: Cast and nullable types - @thekid
* Added support for casting value and array types - @thekid

## 0.9.1 / 2017-10-21

* Fixed promoted argument types not being recorded - @thekid

## 0.9.0 / 2017-10-21

* Added support for `$arg ==> $arg++` lambdas without argument braces
  (@thekid)
* Fixed issue #8: Member types missing for constructor argument promotion
  (@thekid)
* Fixed issue #7: Ternary operator broken - @thekid
* Fixed issue #6: instanceof does not resolve class names - @thekid
* Implemented support for union types, e.g. `int|float`, as supported
  by [this PHP RFC](https://wiki.php.net/rfc/union_types)
  (@thekid)
* Implemented `array<int>` and `array<string, string>` as well as
  function types (e.g. `(function(int, string): string)`) as seen in
  [Hack's type system](https://docs.hhvm.com/hack/types/type-system)
  (@thekid)

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
