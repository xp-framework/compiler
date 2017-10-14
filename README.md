XP Compiler
===========

[![Build Status on TravisCI](https://secure.travis-ci.org/xp-forge/sequence.svg)](http://travis-ci.org/xp-framework/ast)
[![XP Framework Module](https://raw.githubusercontent.com/xp-framework/web/master/static/xp-framework-badge.png)](https://github.com/xp-framework/core)
[![BSD Licence](https://raw.githubusercontent.com/xp-framework/web/master/static/licence-bsd.png)](https://github.com/xp-framework/core/blob/master/LICENCE.md)
[![Required PHP 5.6+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-5_6plus.png)](http://php.net/)
[![Supports PHP 7.0+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-7_0plus.png)](http://php.net/)
[![Supports HHVM 3.4+](https://raw.githubusercontent.com/xp-framework/web/master/static/hhvm-3_4plus.png)](http://hhvm.com/)
[![Latest Stable Version](https://poser.pugx.org/xp-framework/ast/version.png)](https://packagist.org/packages/xp-forge/sequence)

Compiles future PHP to today's PHP.

Usage
-----
After adding the compiler to your project via `composer xp-framework/compiler` classes will be passed through the compiler during autoloading. Code inside files with a *.class.php* ending is considered already compiled; files need to renamed `T.class.php` => `T.php` in order to be picked up.

Example
-------
The following code uses PHP 7.1 and PHP 7.0 features but runs on anything >= PHP 5.6. Builtin features from newer PHP versions are translated to work with the currently executing runtime if necessary.

```php
<?php // In a file "HelloWorld.php"

use util\cmd\Console;

class HelloWorld {

  public static function main(array $args): void {
    Console::writeLine('Hello, ', $args[0] ?? 'World', '!');
  }
}
```

Features supported
------------------

The following table gives an overview of the current implementation status. The goal is to have check marks everywhere!

| Feature                                                                   | PHP 5.6  | PHP 7.0  | PHP 7.1  | PHP 7.2  |
| ------------------------------------------------------------------------- | -------- | -------- | -------- | -------- |
| **Future** - these might or might not be part of PHP next                 |          |          |          |          |
| [Property types](https://wiki.php.net/rfc/property_type_hints)            | ✔      | ✔      | ✔       | ✔       |
| [Hack lambdas (==>)](https://docs.hhvm.com/hack/operators/lambda)         | ✔      | ✔      | ✔       | ✔       |
| [Compact functions](https://github.com/xp-framework/rfc/issues/241)       | ✔      | ✔      | ✔       | ✔       |
| [Hack attributes](https://docs.hhvm.com/hack/attributes/introduction) (as XP annotations) | ✔ | ✔ | ✔ | ✔       |
| [Constructor argument promotion](https://docs.hhvm.com/hack/other-features/constructor-parameter-promotion) | ✔ | ✔ | ✔ | ✔ |
| **PHP 7.2**                                                               |          |          |          |          |
| [Object type](https://wiki.php.net/rfc/object-typehint)                   | ✔(1)   | ✔(1)   | ✔(1)    | *native*  |
| **PHP 7.1**                                                               |          |          |          |          |
| [Multiple catch](https://wiki.php.net/rfc/multiple-catch)                 | ✔      | ✔      | *native* | *native*  |
| [Void](https://wiki.php.net/rfc/void_return_type)                         | ✔(1)   | ✔(1)   | *native* | *native*  |
| [Iterable](https://wiki.php.net/rfc/iterable)                             | ✔(1)   | ✔(1)   | *native* | *native*  |
| [Constant modifiers](https://wiki.php.net/rfc/class_const_visibility)     | ✖      | ✖       | *native* | *native* |
| [Short list syntax](https://wiki.php.net/rfc/short_list_syntax)           | ✖      | ✖       | ✖      | ✖       |
| [Nullabe types](https://wiki.php.net/rfc/nullable_types)                  | ✖      | ✖       | ✖      | ✖       |
| **PHP 7.0**                                                               |          |          |          |          |
| [Grouped use](https://wiki.php.net/rfc/group_use_declarations)            | ✖      | *native* | *native* | *native*  |
| [Null coalesce (??)](https://wiki.php.net/rfc/isset_ternary)              | ✔      | *native* | *native* | *native*  |
| [Comparison (<=>)](https://wiki.php.net/rfc/combined-comparison-operator) | ✖      | *native* | *native* | *native*  |
| [Scalar types](https://wiki.php.net/rfc/scalar_type_hints_v5)             | ✔(1)   | *native* | *native* | *native*  |
| [Return types](https://wiki.php.net/rfc/return_types)                     | ✔(1)   | *native* | *native* | *native*  |
| [Variable syntax](https://wiki.php.net/rfc/uniform_variable_syntax)       | ✔      | *native* | *native* | *native*  |
| [Anonymous classes](https://wiki.php.net/rfc/anonymous_classes)           | ✖      | *native* | *native* | *native*  |
| [Generator "yield from"](https://wiki.php.net/rfc/generator-delegation)   | ✖      | *native* | *native* | *native*  |
| [Generator return](https://wiki.php.net/rfc/generator-return-expressions) | ✖      | *native* | *native* | *native*  |

*1: Currently unchecked*.

* * *

Here's some more code:

```php
<?php namespace test;

use util\cmd\Console;
use peer\http\HttpConnection;

// https://wiki.php.net/rfc/group_use_declarations
use peer\{ConnectException, SocketException};

// https://wiki.php.net/rfc/variadics
function println(... $_) {

  // https://wiki.php.net/rfc/argument_unpacking
  return Console::writeLine(...$_);
}

class Uri {
  // https://docs.hhvm.com/hack/types/annotations#class-properties
  // (https://wiki.php.net/rfc/property_type_hints)
  private HttpConnection $conn= null;

  // https://docs.hhvm.com/hack/other-features/constructor-parameter-promotion
  public function __construct(private string $uri) { }

  // https://github.com/xp-framework/rfc/issues/241
  public function __toString(): string ==> $this->uri;

  // https://docs.hhvm.com/hack/attributes/introduction
  <<deprecated('Use connection() instead')>>
  public function getConnection() ==> $this->connection();

  // https://wiki.php.net/rfc/isset_ternary
  public function connection(): HttpConnection {
    return $this->conn ?? $this->conn= new HttpConnection($this);
  }
}

// https://docs.hhvm.com/hack/operators/lambda
// (https://wiki.php.net/rfc/arrow_functions)
$get= (Uri $uri) ==> $uri->connection()->get();

try {
  $r= $get(new Uri($argv[1] ?? 'http://localhost'));
  println($r);

  $s= $r->header('Content-Length')[0];
  println('read(', $s, ') => ', strlen($r->readData($s)));
} catch (ConnectException | SocketException $e) {

  // https://wiki.php.net/rfc/multiple-catch
  println('Error ', $e->compoundMessage());
} finally {
  println('Done');
}
```