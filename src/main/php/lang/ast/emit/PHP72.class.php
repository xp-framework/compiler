<?php namespace lang\ast\emit;

use lang\ast\Emitter;

/**
 * PHP 7.2 syntax
 *
 * @see  https://wiki.php.net/rfc#php_72
 */
class PHP72 extends Emitter {
  use OmitPropertyTypes;
  use RewriteNullCoalesceAssignment, RewriteLambdaExpressions;

  protected $unsupported= [
    'mixed'    => null,
  ];
}