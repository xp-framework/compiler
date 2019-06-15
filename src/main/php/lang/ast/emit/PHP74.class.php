<?php namespace lang\ast\emit;

use lang\ast\Emitter;

/**
 * PHP 7.4 syntax
 *
 * @see  https://wiki.php.net/rfc#php_74
 */
class PHP74 extends Emitter {
  use RewriteBlockLambdaExpressions;

  protected $unsupported= [
    'mixed'    => null,
  ];
}