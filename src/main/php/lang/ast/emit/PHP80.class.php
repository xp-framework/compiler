<?php namespace lang\ast\emit;

use lang\ast\Emitter;

/**
 * PHP 8.0 syntax
 *
 * @see  https://wiki.php.net/rfc#php_80
 */
class PHP80 extends Emitter {
  use RewriteBlockLambdaExpressions;

  protected $unsupported= [
    'mixed'    => null,
  ];
}