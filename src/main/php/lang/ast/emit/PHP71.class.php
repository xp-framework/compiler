<?php namespace lang\ast\emit;

use lang\ast\Emitter;

/**
 * PHP 7.1 syntax
 *
 * @see  https://wiki.php.net/rfc#php_71
 */
class PHP71 extends Emitter {
  use OmitPropertyTypes;
  use RewriteNullCoalesceAssignment, RewriteLambdaExpressions;

  protected $unsupported= [
    'object'   => 72,
    'mixed'    => null,
  ];
}