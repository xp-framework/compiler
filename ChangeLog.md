XP Compiler ChangeLog
=====================

## ?.?.? / ????-??-??

## 8.8.3 / 2023-02-12

* Merged PR #156: Do not error at compile time for unresolved classes
  (@thekid)

## 8.8.2 / 2023-02-12

* Fixed emitted meta information for generic declarations - @thekid
* Merged PR #153: Migrate to new testing library - @thekid

## 8.8.1 / 2023-01-29

* Added flag to meta information to resolve ambiguity when exactly one
  unnamed annotation argument is present and an array or NULL. See
  xp-framework/reflection#27
  (@thekid)

## 8.8.0 / 2022-12-04

* Merged PR #152: Make enclosing type(s) accessible via code generator
  (@thekid)

## 8.7.0 / 2022-11-12

* Fixed issue #31: Support `list()` reference assignment for PHP < 7.3
  (@thekid)
* Fixed `foreach` statement using destructuring assignments in PHP 7.0
  (@thekid)
* Merged PR #146: Add support for omitting expressions in destructuring
  assignments, e.g. `list($a, , $b)= $expr` or `[, $a]= $expr`.
  (@thekid)
* Merged PR #145: Add support for specifying keys in list(), which has
  been in PHP since 7.1.0, see https://wiki.php.net/rfc/list_keys
  (@thekid)
* Merged PR #144: Rewrite `list(&$a)= $expr` for PHP 7.0, 7.1 and 7.2
  (@thekid)

## 8.6.0 / 2022-11-06

* Bumped dependency on `xp-framework/ast` to version 9.1.0 - @thekid
* Merged PR #143: Adapt to AST type refactoring (xp-framework/ast#39)
  (@thekid)

## 8.5.1 / 2022-09-03

* Fixed issue #142: Nullable type unions don't work as expected - @thekid

## 8.5.0 / 2022-07-20

* Merged PR #140: Add support for null, true and false types - @thekid

## 8.4.0 / 2022-05-14

* Merged PR #138: Implement readonly modifier for classes - a PHP 8.2
  feature, see https://wiki.php.net/rfc/readonly_classes
  (@thekid)
* Merged PR #135: Move responsibility for creating result to emitter
  (@thekid)

## 8.3.0 / 2022-03-07

* Made JIT class loader ignore *autoload.php* files - @thekid
* Fixed #136: Line number inconsistent after multi-line doc comments
  (@thekid)

## 8.2.0 / 2022-01-30

* Support passing emitter-augmenting class names to the instanceFor()
  method of `lang.ast.CompilingClassLoader`.
  (@thekid)

## 8.1.0 / 2022-01-29

* Merged PR #131: Inline nullable checks when casting - @thekid

## 8.0.0 / 2022-01-16

This release is the first in a series of releases to make the XP compiler
more universally useful: Compiled code now doesn't include generated XP
meta information by default, and is thus less dependant on XP Core, see
https://github.com/xp-framework/compiler/projects/4.

* Merged PR #129: Add augmentable emitter to create property type checks
  for PHP < 7.4
  (@thekid)
* Fixed private and protected readonly properties being accessible from
  any scope in PHP < 8.1
  (@thekid)
* Merged PR #127: Do not emit XP meta information by default. This is
  the first step towards generating code that runs without a dependency
  on XP core.
  (@thekid)

## 7.3.0 / 2022-01-07

* Merged PR #128: Add support for static closures - @thekid
* Upgraded dependency on `xp-framework/ast` to version 8.0.0 - @thekid

## 7.2.1 / 2021-12-28

* Fixed PHP 8.1 not emitting native callable syntax - @thekid
* Fixed `isConstant()` for constant arrays - @thekid

## 7.2.0 / 2021-12-20

* Optimized generated code for arrays including unpack expressions for
  PHP 8.1+, which natively supports unpacking with keys. See
  https://wiki.php.net/rfc/array_unpacking_string_keys
  (@thekid)

## 7.1.0 / 2021-12-08

* Added preliminary PHP 8.2 support by fixing various issues throughout
  the code base and adding a PHP 8.2 emitter.
  (@thekid)

## 7.0.0 / 2021-10-21

This major release drops compatiblity with older XP versions.

* Made compatible with XP 11 - @thekid
* Implemented xp-framework/rfc#341, dropping compatibility with XP 9
  (@thekid)

## 6.11.0 / 2021-10-06

* Merged PR #125: Support `new T(...)` callable syntax - @thekid

## 6.10.0 / 2021-09-12

* Implemented feature request #123: Use `php:X.Y` instead of *PHP.X.Y*
  for target runtimes. The older syntax is still supported!
  (@thekid)

## 6.9.0 / 2021-09-12

* Merged PR #124: Add support for readonly properties. Implements feature
  request #115, using native code for PHP 8.1 and simulated via virtual
  properties for PHP 7.X and PHP 8.0
  (@thekid)

## 6.8.3 / 2021-09-11

* Fixed types not being emitted for promoted properties - @thekid

## 6.8.2 / 2021-09-09

* Fixed *Call to undefined method ...::emitoperator())* which do not
  provide any context as to where an extraneous operator was encountered.
  (@thekid)

## 6.8.1 / 2021-08-16

* Fixed issue #122: Undefined constant `Sources::FromEmpty` (when using
  PHP 8.1 native enumerations in PHP 8.0 and lower *and* the JIT compiler)
  (@thekid)

## 6.8.0 / 2021-08-04

* Merged PR #120: Support for PHP 8.1 intersection types, accessible via
  reflection in all PHP versions, and runtime type-checked with 8.1.0+
  (@thekid)

## 6.7.0 / 2021-07-12

* Changed emitter to omit extra newlines between members, making line
  numbers more consistent with the original code.
  (@thekid)
* Merged PR #114: Implements first-class callable syntax: `strlen(...)`
  now returns a closure which if invoked with a string argument, returns
  its length. Includes support for static and instance methods as well as
  indirect references like `$closure(...)` and `self::{$expression}(...)`,
  see https://wiki.php.net/rfc/first_class_callable_syntax
  (@thekid)

## 6.6.0 / 2021-07-10

* Emit null-coalesce operator as `$a ?? $a= expression` instead of as
  `$a= $a ?? expression`, saving one assignment operation for non-null
  case. Applies to PHP 7.0, 7.1 and 7.2.
  (@thekid)
* Removed conditional checks for PHP 8.1 with native enum support, all
  releases and builds available on CI systems now contain it.
  (@thekid)
* Increased test coverage significantly (to more than 90%), especially
  for classes used by the compiler command line.
  (@thekid)

## 6.5.0 / 2021-05-22

* Merged PR #111: Add support for directives using declare - @thekid

## 6.4.0 / 2021-04-25

* Merged PR #110: Rewrite `never` return type to void in PHP < 8.1, adding
  support for this PHP 8.1 feature. XP Framework reflection supports this
  as of its 10.10.0 release.
  (@thekid)

## 6.3.2 / 2021-03-14

* Allowed `new class() extends self` inside class declarations - @thekid

## 6.3.1 / 2021-03-14

* Fix being able to clone enum lookalikes - @thekid
* Fix `clone` operator - @thekid

## 6.3.0 / 2021-03-13

* Merged PR #106: Compile PHP enums to PHP 7/8 lookalikes, PHP 8.1 native.
  (@thekid)

## 6.2.0 / 2021-03-06

* Merged PR #104: Support arbitrary expressions in property initializations
  and parameter defaults
  (@thekid)

## 6.1.1 / 2021-01-04

* Fixed issue #102: Call to a member function children()... - @thekid
* Fixed issue #102: PHP 8.1 compatibility - @thekid
* Fixed issue #103: Nullable types - @thekid

## 6.1.0 / 2021-01-04

* Included languages and emitters in `xp compile` output - @thekid

## 6.0.0 / 2020-11-28

This major release removes legacy XP and Hack language annotations as
well as curly braces for string and array offsets. It also includes the
first PHP 8.1 features.

* Added `-q` command line option which suppresses all diagnostic output
  from the compiler except for errors
  (@thekid)
* Removed support for using curly braces as offset (e.g. `$value{0}`)
  (@thekid)
* Merged PR #92: Add support for explicit octal integer literal notation
  See https://wiki.php.net/rfc/explicit_octal_notation (PHP 8.1)
  (@thekid)
* Merged PR #93: Allow match without expression: `match { ... }`. See
  https://wiki.php.net/rfc/match_expression_v2#allow_dropping_true
  (@thekid)
* Removed support for legacy XP and Hack language annotations, see #86
  (@thekid)
* Merged PR #96: Enclose blocks where PHP only allows expressions. This
  not only allows `fn() => { ... }` but also using blocks in `match`.
  (@thekid)

## 5.7.0 / 2020-11-26

* Verified full PHP 8 support now that PHP 8.0.0 has been released,
  see https://www.php.net/archive/2020.php#2020-11-26-3
  (@thekid)
* Merged PR #95: Support compiling to XAR archives - @thekid

## 5.6.0 / 2020-11-22

* Merged PR #94: Add support for `static` return type - @thekid
* Optimized null-safe instance operator for PHP 8.0 - @thekid
* Added PHP 8.1-dev to test matrix now that is has been branched
  (@thekid)
* Added support for non-capturing catches, see this PHP 8 RFC:
  https://wiki.php.net/rfc/non-capturing_catches
  (@thekid)

## 5.5.0 / 2020-11-15

* Merged PR #91 - Refactor rewriting type literals:
  - Changed implementation to be easier to maintain
  - Emit function types as `callable` in all PHP versions
  - Emit union types as syntax in PHP 8+
  (@thekid)

## 5.4.1 / 2020-10-09

* Fixed #90: Namespace declaration statement has to be the very first 
  statement, which occured with PHP 8.0.0RC1
  (@thekid)

## 5.4.0 / 2020-09-12

* Implemented second step for #86: Add an E_DEPRECATED warning to the
  hacklang annotation syntax `<<...>>`; details in xp-framework/ast#9
  (@thekid)
* Merged PR #89: Add annotation type mappings to `TARGET_ANNO` detail
  (@thekid)
* Changed PHP 8 attributes to be emitted in XP meta information without
  namespaces, and with their first characters lowercased. This way, code
  using annotations will continue to work, see xp-framework/rfc#336.
  (@thekid)

## 5.3.0 / 2020-09-12

* Merged PR #88: Emit named arguments for PHP 8 - @thekid

## 5.2.1 / 2020-09-09

* Adjusted to `xp-framework/ast` yielding comments as-is, transform
  them to the form XP meta information expects.
  (@thekid)

## 5.2.0 / 2020-07-20

* Merged PR #87: Add support for match expression - @thekid
* Implemented first step of #86: Support PHP 8 attributes - @thekid
* Removed `lang.ast.syntax.php.NullSafe` in favor of builtin support
  (@thekid)
* Merged PR #84: Extract parser - @thekid

## 5.1.3 / 2020-04-04

* Allowed `::class` on objects (PHP 8.0 forward compatibility) - @thekid

## 5.1.2 / 2020-04-04

* Fixed promotion for by-reference arguments - @thekid

## 5.1.1 / 2020-03-29

* Fixed ternary and instanceof operators' precedence - @thekid

## 5.1.0 / 2020-03-28

* Merged PR #82: Allow chaining scope resolution operator `::` - @thekid
* Merged PR #81: Allow `instanceof (<expr>)` as syntax - @thekid
* Merged PR #80: Allow `new (<expr>)` as syntax - @thekid

## 5.0.0 / 2019-11-30

This major release drops PHP 5 support. The minimum required PHP version
is now 7.0.0.

* Merged PR #70: Extract compact methods; to use these, require the
  library https://github.com/xp-lang/php-compact-methods
  (@thekid)
* Merged PR #79: Convert testsuite to baseless tests - @thekid
* Merged PR #78: Deprecate curly brace syntax for offsets; consistent
  with PHP 7.4
  (@thekid)
* Added support for XP 10 and newer versions of library dependencies
  (@thekid)
* Implemented xp-framework/rfc#334: Drop PHP 5.6. The minimum required
  PHP version is now 7.0.0!
  (@thekid)

## 4.3.1 / 2019-11-30

* Added compatibility with XP 10, see xp-framework/rfc#333 - @thekid

## 4.3.0 / 2019-11-24

* Fixed global constants in ternaries being ambiguous with goto labels
  (@thekid)
* Fixed emitting `switch` statements and case labels' ambiguity w/ goto
  (@thekid)
* Fixed an operator precedence problem causing incorrect nesting in the
  parsed AST for unary prefix operators.
  (@thekid)
* Merged PR #77: Add support for #-style comments including support for
  XP style annotations
  (@thekid)

## 4.2.1 / 2019-10-05

* Fixed parser to allow "extending" final and abstract types - @thekid

## 4.2.0 / 2019-10-04

* Fixed issue #74: No longer shadow compiler errors in certain cases
  (@thekid)
* Merged PR #75: Add "ast" subcommand to display the abstract syntax tree
  (@thekid)

## 4.1.0 / 2019-10-01

* Merged PR #73: Add support for annotations in anonymous classes
  (@thekid)

## 4.0.0 / 2019-09-09

This major release adds an extension mechanisms. Classes inside the package
`lang.ast.syntax.php` (regardless of their class path) will be loaded auto-
matically on startup.

* Merged PR #69: Remove support for Hack arrow functions - @thekid
* Fixed operator precedence for unary prefix operators - @thekid
* Merged PR #66: Syntax plugins. With this facility in place, the compiler
  can be extended much like [Babel](https://babeljs.io/docs/en/plugins).
  This is useful for adapting features which may or may not make it into
  PHP one day. Current extensions like compact methods are kept for BC
  reasons, but will be extracted into their own libraries in the future!
  (@thekid)

## 3.0.0 / 2019-08-10

This release aligns XP Compiler compatible with PHP 7.4 and changes it
to try to continue parsing after an error has occured, possibly yielding
multiple errors.

* Made compatible with PHP 7.4 - refrain using `{}` for string offsets
  (@thekid)
* Merged PR #45 - Multiple errors - @thekid
* Changed compiler to emit deprecation warnings for Hack language style
  arrow functions and compact methods using `==>`, instead advocating the
  use of PHP 7.4 with the `fn` keyword; see issue #65
  (@thekid)

## 2.13.0 / 2019-06-15

* Added preliminary PHP 8 support - see #62 (@thekid)
* Added [support for PHP 7.4 features](https://github.com/xp-framework/compiler/projects/2)
  - Implemented numeric literal separator, e.g. `1_000_000_000` - see #61
  - Implemented null-colaesce assignment operator `??=` - see #58
  - Implemented support PHP 7.4-style short closures with `fn` - see #60
  - Implemented support for emitting typed properties in PHP 7.4 - see #57
  (@thekid)

## 2.12.0 / 2018-12-28

* Added support for [PHP 7.3](http://php.net/archive/2018.php#id2018-12-06-1)
  (@thekid)

## 2.11.1 / 2018-09-14

* Fixed #56: Resolved paths not absolute - @thekid

## 2.11.0 / 2018-08-11

* Merged PR #54: Ensure filenames in errors reflect source files - @thekid

## 2.10.1 / 2018-06-24

* Fixed throw expressions with variables, e.g. `() ==> throw $e;`.
  (@thekid)
* Fixed URI loading for CompilingClassLoader - this way, loading
  classes via URIs works, and thus e.g. `xp test path/to/Test.php`.
  (@thekid)

## 2.10.0 / 2018-06-21

* Merged PR #53: Implement throw expressions - @thekid

## 2.9.0 / 2018-06-19

* Merged PR #52: Implement "goto" statement - @thekid
* Merged PR #50: Allow arrow functions with blocks - @thekid

## 2.8.0 / 2018-06-17

* Merged PR #47: Allow empty catch type to catch all exceptions - @thekid
* Merged PR #44: Implement `echo` statement - @thekid

## 2.7.0 / 2018-06-16

* Merged PR #43: Add "-n" command line option to compile only - @thekid
* Merged PR #42: Raise errors when members are redeclared - @thekid

## 2.6.2 / 2018-06-16

* Fixed issue with dynamic instance references not being emitted
  correctly, e.g. `$value->{$field->get()};`, see
  http://php.net/manual/en/functions.variable-functions.php
  (@thekid)

## 2.6.1 / 2018-06-10

* Fixed issue #41: String parsing of escapes broken - @thekid

## 2.6.0 / 2018-06-10

* Implemented support unicode escape sequences in PHP 5.6, see #38
  (@thekid)
* Fixed issue #39: Syntax error for parameters called "function" 
  (@thekid)
* Dropped confusing way of compiling multiple sources using `-b` to
  strip bases. New way is to pass multiple directories directly, e.g.
  `$ xp compile -o dist src/main/php/ src/test/php`
  (@thekid)
* Fixed compiling to a directory when the source path was not inside
  the current directory.
  (@thekid)

## 2.5.1 / 2018-06-10

* Ensured line number is always present for type members. Previously,
  this was 0, leading to output formatting errors
  (@thekid)
* Made some minor performance improvements by reusing nodes in two
  cases - return statements and assignments
  (@thekid)

## 2.5.0 / 2018-06-09

* Implemented feature request #9: Support null-safe instance operator
  (@thekid)

## 2.4.0 / 2018-06-08

* Added context to various parse errors. Now messages read something
  like `Expected ",", have "(end)"" in parameter list`.
  (@thekid)
* Improved error messages: Include file name (w/o full path) and line
  number in exceptions raised from class loading.
  (@thekid)

## 2.3.0 / 2018-04-02

* Merged PR #33: Using statement - @thekid

## 2.2.0 / 2018-03-30

* Fixed typed properties inside comma-separated listing, for example:
  `private string $a, int $b`
  (@thekid)
* Implemented support for typed class constants `const int T = 5`
  (@thekid)

## 2.1.0 / 2018-03-29

* Implemented support for `mixed` type, see issue #28 - @thekid
* Fixed issue #32: Test suite failure on HHVM 3.25 - @thekid
* Allowed trailing commas in grouped use lists as implemented in
  https://wiki.php.net/rfc/list-syntax-trailing-commas
  (@thekid)
* Fixed nullable value types being emitted incorrectly - @thekid
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

This major release extracts the AST API to its own library, and cleans it
up while doing so. The idea is to be able to use this library in other
places in the future.

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

This first release brings consistency to annotations, casting and how
and where keywords can be used. XP Compiler is already being used in
production in an internal project at the time of writing, so you might
do so too:)

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
