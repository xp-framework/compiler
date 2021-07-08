<?php namespace lang\ast\emit;

use lang\ast\types\{IsUnion, IsFunction, IsArray, IsMap, IsNullable, IsValue, IsLiteral};

/**
 * PHP 7.4 syntax
 *
 * @see  https://wiki.php.net/rfc#php_74
 */
class PHP74 extends PHP {
  use RewriteBlockLambdaExpressions, RewriteClassOnObjects, RewriteExplicitOctals, RewriteEnums;

  /** Sets up type => literal mappings */
  public function __construct() {
    $this->literals= [
      IsArray::class    => function($t) { return 'array'; },
      IsMap::class      => function($t) { return 'array'; },
      IsFunction::class => function($t) { return 'callable'; },
      IsValue::class    => function($t) { $l= $t->literal(); return 'static' === $l ? 'self' : $l; },
      IsNullable::class => function($t) { $l= $this->literal($t->element); return null === $l ? null : '?'.$l; },
      IsUnion::class    => function($t) { return null; },
      IsLiteral::class  => function($t) {
        $l= $t->literal();
        return 'mixed' === $l ? null : ('never' === $l ? 'void' : $l);
      },
    ];
  }
}