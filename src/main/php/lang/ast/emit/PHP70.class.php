<?php namespace lang\ast\emit;

use lang\ast\types\{IsUnion, IsFunction, IsArray, IsMap, IsNullable, IsValue, IsLiteral};

/**
 * PHP 7.0 syntax
 *
 * @see  https://wiki.php.net/rfc#php_70
 */
class PHP70 extends PHP {
  use OmitPropertyTypes, OmitConstModifiers, CallablesAsClosures;
  use RewriteNullCoalesceAssignment, RewriteLambdaExpressions, RewriteMultiCatch, RewriteClassOnObjects, RewriteExplicitOctals;

  /** Sets up type => literal mappings */
  public function __construct() {
    $this->literals= [
      IsFunction::class => function($t) { return 'callable'; },
      IsArray::class    => function($t) { return 'array'; },
      IsMap::class      => function($t) { return 'array'; },
      IsValue::class    => function($t) { $l= $t->literal(); return 'static' === $l ? 'self' : $l; },
      IsNullable::class => function($t) { return null; },
      IsUnion::class    => function($t) { return null; },
      IsLiteral::class  => function($t) {
        $l= $t->literal();
        return ('object' === $l || 'void' === $l || 'iterable' === $l || 'mixed' === $l || 'never' === $l) ? null : $l;
      },
    ];
  }
}