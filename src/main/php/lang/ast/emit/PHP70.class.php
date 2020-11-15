<?php namespace lang\ast\emit;

use lang\ast\types\{IsUnion, IsFunction, IsArray, IsMap, IsNullable, IsValue, IsLiteral};

/**
 * PHP 7.0 syntax
 *
 * @see  https://wiki.php.net/rfc#php_70
 */
class PHP70 extends PHP {
  use OmitPropertyTypes, OmitConstModifiers;
  use RewriteNullCoalesceAssignment, RewriteLambdaExpressions, RewriteMultiCatch, RewriteClassOnObjects;

  /** Sets up type => literal mappings */
  public function __construct() {
    $this->literals= [
      IsUnion::class    => function($t) { return null; },
      IsFunction::class => function($t) { return null; },
      IsNullable::class => function($t) { return null; },
      IsArray::class    => function($t) { return 'array'; },
      IsMap::class      => function($t) { return 'array'; },
      IsValue::class    => function($t) { return $t->literal(); },
      IsLiteral::class  => function($t) {
        $l= $t->literal();
        return ('object' === $l || 'void' === $l || 'iterable' === $l || 'mixed' === $l) ? null : $l;
      },
    ];
  }
}