XP Compiler
===========

[![Build status on GitHub](https://github.com/xp-framework/compiler/workflows/Tests/badge.svg)](https://github.com/xp-framework/compiler/actions)
[![XP Framework Module](https://raw.githubusercontent.com/xp-framework/web/master/static/xp-framework-badge.png)](https://github.com/xp-framework/core)
[![BSD Licence](https://raw.githubusercontent.com/xp-framework/web/master/static/licence-bsd.png)](https://github.com/xp-framework/core/blob/master/LICENCE.md)
[![Requires PHP 7.4+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-7_4plus.svg)](http://php.net/)
[![Supports PHP 8.0+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-8_0plus.svg)](http://php.net/)
[![Latest Stable Version](https://poser.pugx.org/xp-framework/compiler/version.svg)](https://packagist.org/packages/xp-framework/compiler)

Compiles future PHP to today's PHP.

Usage
-----
After adding the compiler to your project via `composer require xp-framework/compiler`, it will hook into the class loading chain and compile `.php`-files on-demand. This keeps the efficient code-save-reload/rerun development process typical for PHP.

Example
-------
The following code uses Hack language, PHP 8.4, PHP 8.3, PHP 8.2, 8.1 and 8.0 features but runs on anything >= PHP 7.4. Builtin features from newer PHP versions are translated to work with the currently executing runtime if necessary.

```php
<?php // In a file "HelloWorld.php"

use lang\Reflection;
use util\Date;
use util\cmd\Console;

#[Author('Timm Friebe')]
#[Permissions(0o777)]
class HelloWorld {
  private const string GREETING= 'Hello';

  public static function main(array<string> $args): void {
    $greet= fn($to, $from) => self::GREETING.' '.$to.' from '.$from;
    $author= Reflection::type(self::class)->annotation(Author::class)->argument(0);

    Console::writeLine(new Date()->toString(), ': ', $greet($args[0] ?? 'World', from: $author));
  }
}
```

To run this code, use `xp -m /path/to/xp/reflection HelloWorld` in a terminal window.

Compilation
-----------
Compilation can also be performed explicitely by invoking the compiler:

```bash
# Compile code and write result to a class file
$ xp compile HelloWorld.php HelloWorld.class.php

# Compile standard input and write to standard output.
$ echo "<?php ..." | xp compile -

# Compile src/main/php and src/test/php to the dist folder.
$ xp compile -o dist src/main/php/ src/test/php/

# Compile src/main/php to the dist.xar archive
$ xp compile -o dist.xar src/main/php/

# Compile src/main/php, do not write output
$ xp compile -n src/main/php/

# Target PHP 7.4 (default target is current PHP version)
$ xp compile -t php:7.4 HelloWorld.php HelloWorld.class.php

# Emit XP meta information (includes lang.ast.emit.php.XpMeta):
$ xp compile -t php:7.4 -a php:xp-meta -o dist src/main/php
```

The -o and -n options accept multiple input sources following them.
The -q option suppresses all diagnostic output except for errors.

Features supported
------------------

XP Compiler supports features such as annotations, arrow functions, enums, property type-hints, the null-safe instance operator as well as all PHP 7 and PHP 8 syntax additions. A complete list including examples can be found [in our Wiki](https://github.com/xp-framework/compiler/wiki).

### More features

Additional syntax like an `is` operator, generics or record types can be added by installing compiler plugins from [here](https://github.com/xp-lang):

```bash
$ composer require xp-lang/php-is-operator
# ...

$ xp compile
Usage: xp compile <in> [<out>]

@FileSystemCL<./vendor/xp-framework/compiler/src/main/php>
lang.ast.emit.PHP74
lang.ast.emit.PHP80
lang.ast.emit.PHP81
lang.ast.emit.PHP82
lang.ast.emit.PHP83 [*]
lang.ast.emit.PHP84
lang.ast.emit.PHP85
lang.ast.syntax.php.Using [*]

@FileSystemCL<./vendor/xp-lang/php-is-operator/src/main/php>
lang.ast.syntax.php.IsOperator
```

Implementation status
---------------------

Some features from newer PHP versions as well as Hack language are still missing. The goal, however, is to have all features implemented - with the exception of where Hack's direction conflicts with PHP! An overview can be seen [on this Wiki page](https://github.com/xp-framework/compiler/wiki/Implementation-status).

To contribute, open issues and/or pull requests.

See also
--------

* [XP Compiler design](https://github.com/xp-framework/compiler/wiki/Compiler-design)
* [XP RFC #0299: Make XP compiler the Babel of PHP](https://github.com/xp-framework/rfc/issues/299)
* [XP RFC #0327: Compile-time metaprogramming](https://github.com/xp-framework/rfc/issues/327)