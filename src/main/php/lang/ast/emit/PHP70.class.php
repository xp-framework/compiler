<?php namespace lang\ast\emit;

/**
 * PHP 7.0 syntax
 *
 * @see  https://wiki.php.net/rfc#php_70
 */
class PHP70 extends PHP {
  use OmitPropertyTypes, OmitConstModifiers;
  use RewriteNullCoalesceAssignment, RewriteLambdaExpressions, RewriteMultiCatch;

  protected $unsupported= [
    'object'   => 72,
    'void'     => 71,
    'iterable' => 71,
    'mixed'    => null,
  ];
}