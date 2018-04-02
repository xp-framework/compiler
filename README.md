XP Compiler
===========

[![Build Status on TravisCI](https://secure.travis-ci.org/xp-forge/sequence.svg)](http://travis-ci.org/xp-framework/compiler)
[![XP Framework Module](https://raw.githubusercontent.com/xp-framework/web/master/static/xp-framework-badge.png)](https://github.com/xp-framework/core)
[![BSD Licence](https://raw.githubusercontent.com/xp-framework/web/master/static/licence-bsd.png)](https://github.com/xp-framework/core/blob/master/LICENCE.md)
[![Required PHP 5.6+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-5_6plus.png)](http://php.net/)
[![Supports PHP 7.0+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-7_0plus.png)](http://php.net/)
[![Supports HHVM 3.20+](https://raw.githubusercontent.com/xp-framework/web/master/static/hhvm-3_20plus.png)](http://hhvm.com/)
[![Latest Stable Version](https://poser.pugx.org/xp-framework/compiler/version.png)](https://packagist.org/packages/xp-framework/compiler)

Compiles future PHP to today's PHP.

Usage
-----
After adding the compiler to your project via `composer require xp-framework/compiler` classes will be passed through the compiler during autoloading. Code inside files with a *.class.php* ending is considered already compiled; files need to renamed `T.class.php` => `T.php` in order to be picked up.

Example
-------
The following code uses Hack, PHP 7.1 and PHP 7.0 features but runs on anything >= PHP 5.6. Builtin features from newer PHP versions are translated to work with the currently executing runtime if necessary.

```php
<?php // In a file "HelloWorld.php"

use util\cmd\Console;
use lang\Type;

<<author('Timm Friebe')>>
class HelloWorld {
  public const GREETING = 'Hello';

  public static function main(array<string> $args): void {
    $greet= ($to, $from) ==> self::GREETING.' '.$to.' from '.$from;
    $author= Type::forName(self::class)->getAnnotation('author');

    Console::writeLine($greet($args[0] ?? 'World', $author));
  }
}
```

Features supported
------------------

The following table gives an overview of the current implementation status. The goal is to have check marks everywhere!

| Feature                                                                                     | PHP 5.6  | PHP 7.0  | PHP 7.1  | PHP 7.2  | PHP 7.3  |
| ------------------------------------------------------------------------------------------- | -------- | -------- | -------- | -------- | -------- |
| **Future** - these might or might not be part of PHP next                                   |          |          |          |          |          |
| • [Mixed type](https://wiki.php.net/rfc/reserve_even_more_types_in_php_7)                   | ✔(1)   | ✔(1)    | ✔(1)   | ✔(1)    | ✔(1)    |
| • [Union types](https://github.com/xp-framework/compiler/wiki/Type-system#type-unions)      | ✔(1)   | ✔(1)    | ✔(1)   | ✔(1)    | ✔(1)    |
| • [XP Compact functions](https://github.com/xp-framework/rfc/issues/241)                    | ✔      | ✔       | ✔      | ✔       | ✔       |
| • [Property types](https://wiki.php.net/rfc/property_type_hints)                            | ✔(1)   | ✔(1)    | ✔(1)   | ✔(1)    | ✔(1)    |
|                                                                                             |          |          |          |          |          |
| **[Hack](https://docs.hhvm.com/hack/)**                                                     |          |          |          |          |          |
| • [Using statement / Disposables](https://docs.hhvm.com/hack/disposables/introduction)      | ✔      | ✔       | ✔      | ✔       | ✔       |
| • [Function types](https://github.com/xp-framework/compiler/wiki/Type-system#functions)     | ✔(1)   | ✔(1)    | ✔(1)   | ✔(1)    | ✔(1)    |
| • [Array and map types](https://github.com/xp-framework/compiler/wiki/Type-system#arrays)   | ✔(1)   | ✔(1)    | ✔(1)   | ✔(1)    | ✔(1)    |
| • [Lambdas / arrow functions](https://github.com/xp-framework/compiler/wiki/Arrow-functions)| ✔      | ✔       | ✔      | ✔       | ✔       |
| • [Attributes](https://docs.hhvm.com/hack/attributes/introduction) (as XP annotations)      | ✔      | ✔       | ✔      | ✔       | ✔       |
| • [Constructor argument promotion](https://docs.hhvm.com/hack/other-features/constructor-parameter-promotion) | ✔ | ✔ | ✔ | ✔     | ✔       |
|                                                                                             |          |          |          |          |          |
| **[PHP 7.3](https://wiki.php.net/rfc#php_73)**                                              |          |          |          |          |          |
| • [Trailing commas in function calls](https://wiki.php.net/rfc/trailing-comma-function-calls) | ✔      | ✔        | ✔        | ✔        | *native* |
|                                                                                             |          |          |          |          |          |
| **[PHP 7.2](https://wiki.php.net/rfc#php_72)**                                              |          |          |          |          |          |
| • [Trailing commas in grouped namepaces](https://wiki.php.net/rfc/list-syntax-trailing-commas) | ✔     | ✔      | ✔       | *native*  | *native* |
| • [Object type](https://wiki.php.net/rfc/object-typehint)                                   | ✔(1)   | ✔(1)   | ✔(1)    | *native*  | *native* |
|                                                                                             |          |          |          |          |          |
| **[PHP 7.1](https://wiki.php.net/rfc#php_71)**                                              |          |          |          |          |          |
| • [Multiple catch](https://wiki.php.net/rfc/multiple-catch)                                 | ✔      | ✔      | *native* | *native*  | *native* |
| • [Void](https://github.com/xp-framework/compiler/wiki/Type-system#void)                    | ✔(1)   | ✔(1)   | *native* | *native*  | *native* |
| • [Iterable](https://github.com/xp-framework/compiler/wiki/Type-system#iteration)           | ✔(1)   | ✔(1)   | *native* | *native*  | *native* |
| • [Constant modifiers](https://wiki.php.net/rfc/class_const_visibility)                     | ✔(1)   | ✔(1)   | *native* | *native*  | *native* |
| • [Short list syntax](https://wiki.php.net/rfc/short_list_syntax)                           | ✔      | ✔      | *native* | *native*  | *native* |
| • [Nullabe types](https://wiki.php.net/rfc/nullable_types)                                  | ✔(1)   | ✔(1)   | *native* | *native*  | *native* |
|                                                                                             |          |          |          |          |          |
| **[PHP 7.0](https://wiki.php.net/rfc#php_70)**                                              |          |          |          |          |          |
| • [Grouped use](https://wiki.php.net/rfc/group_use_declarations)                            | ✔(2)   | ✔(2)    | ✔(2)   | ✔(2)    | ✔(2)    |
| • [Null coalesce (??)](https://wiki.php.net/rfc/isset_ternary)                              | ✔      | *native* | *native* | *native*  | *native* |
| • [Comparison (<=>)](https://wiki.php.net/rfc/combined-comparison-operator)                 | ✔      | *native* | *native* | *native*  | *native* |
| • [Scalar types](https://wiki.php.net/rfc/scalar_type_hints_v5)                             | ✔(1)   | *native* | *native* | *native*  | *native* |
| • [Return types](https://wiki.php.net/rfc/return_types)                                     | ✔(1)   | *native* | *native* | *native*  | *native* |
| • [Variable syntax](https://wiki.php.net/rfc/uniform_variable_syntax)                       | ✔      | *native* | *native* | *native*  | *native* |
| • [Anonymous classes](https://wiki.php.net/rfc/anonymous_classes)                           | ✔      | *native* | *native* | *native*  | *native* |
| • [Keywords as methods](https://wiki.php.net/rfc/context_sensitive_lexer)                   | ✔      | *native* | *native* | *native*  | *native* |
| • [Generator "yield from"](https://wiki.php.net/rfc/generator-delegation)                   | ✔(3)   | *native* | *native* | *native*  | *native* |
| • [Generator return](https://wiki.php.net/rfc/generator-return-expressions)                 | ✖      | *native* | *native* | *native*  | *native* |

Notes:

1. *Currently unchecked.*
2. *Namespaces and imports are resolved by the compiler and not emitted.*
3. *Limited to receiving values currently, sending / throwing not yet implemented*

* * *

See also:

* [XP RFC #0299: Make XP compiler the TypeScript of PHP](https://github.com/xp-framework/rfc/issues/299)
* [XP RFC #0327: Compile-time metaprogramming](https://github.com/xp-framework/rfc/issues/327)