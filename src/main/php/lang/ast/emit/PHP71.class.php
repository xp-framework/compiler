<?php namespace lang\ast\emit;

use lang\ast\types\{IsUnion, IsFunction, IsArray, IsMap, IsNullable, IsValue, IsLiteral};

/**
 * PHP 7.1 syntax
 *
 * @see  https://wiki.php.net/rfc#php_71
 */
class PHP71 extends PHP {
  use OmitPropertyTypes, CallablesAsClosures;
  use RewriteNullCoalesceAssignment, RewriteLambdaExpressions, RewriteClassOnObjects, RewriteExplicitOctals, RewriteEnums;

  /** Sets up type => literal mappings */
  public function __construct() {
    $this->literals= [
      IsFunction::class => function($t) { return 'callable'; },
      IsArray::class    => function($t) { return 'array'; },
      IsMap::class      => function($t) { return 'array'; },
      IsValue::class    => function($t) { $l= $t->literal(); return 'static' === $l ? 'self' : $l; },
      IsNullable::class => function($t) { $l= $this->literal($t->element); return null === $l ? null : '?'.$l; },
      IsUnion::class    => function($t) { return null; },
      IsLiteral::class  => function($t) {
        $l= $t->literal();
        return ('object' === $l || 'mixed' === $l) ? null : ('never' === $l ? 'void' : $l);
      },
    ];
  }
}