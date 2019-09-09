<?php namespace lang\ast\emit;

/**
 * PHP 8.0 syntax
 *
 * @see  https://wiki.php.net/rfc#php_80
 */
class PHP80 extends PHP {
  use RewriteBlockLambdaExpressions;

  protected $unsupported= [
    'mixed'    => null,
  ];
}