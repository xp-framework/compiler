XP Compiler ChangeLog
=====================

## ?.?.? / ????-??-??

## 2.1.0 / ????-??-??

* Merged PR #30: Implement compiling to directory. The command line
  `xp compile src/main/php dist/` will compile all source files inside
  the `src/main/php` directory to `dist`.
  (@thekid)

## 2.0.5 / 2018-02-25

* Fixed apidoc comments for methods, traits and interfaces - @thekid

## 2.0.4 / 2017-11-19

* Fixed issue #27: Class not found - @thekid

## 2.0.3 / 2017-11-16

* Fixed cast on array and map literals, e.g. `(object)['key' => 'value']`
  (@thekid)

## 2.0.2 / 2017-11-14

* Fixed issue #25: Warnings for `return;` - @thekid

## 2.0.1 / 2017-11-06

* Fixed issue #24: Comments contain stars - @thekid

## 2.0.0 / 2017-11-06

* Implemented `use function` and `use const` - @thekid
* Fixed issue #21: Comments are not escaped - @thekid
* Project [AST API](https://github.com/xp-framework/compiler/projects/1):
  - Merged PR #22: Extract AST (to https://github.com/xp-framework/ast)
  - Index annotations by name
  - Split `new` for static and anonymous types
  - Simplified parsing and emitting loops and if/else constructs
  - Renamed `Node::$arity` to `Node::$kind`
  - Merged PR #20: Refactor signature
  - Merged PR #19: Refactor value arrays to specialized types
  (@thekid)

## 1.4.0 / 2017-11-04

* Merged PR #18: Allow using unpack operator inside array literals
  (@thekid)
* Added option to specify target version to `xp compile` - @thekid
* Fixed isse #17: Comments missing from generated code - @thekid

## 1.3.0 / 2017-11-04

* Made it possible to use `<?hh` as opening tag, too. This way, we are
  able to parse Hack language files.
  (@thekid)

## 1.2.1 / 2017-10-31

* Changed ambiguity resolution between casts, braced expressions and
  lambda to be far more robust
  (@thekid)

## 1.2.0 / 2017-10-31

* Added support for import aliases (`use Type as Alias`) - @thekid
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
