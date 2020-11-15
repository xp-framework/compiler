<?php namespace lang\ast\emit;

use lang\ast\types\{IsUnion, IsFunction, IsArray, IsMap, IsNullable, IsValue, IsLiteral};

/**
 * PHP 7.2 syntax
 *
 * @see  https://wiki.php.net/rfc#php_72
 */
class PHP72 extends PHP {
  use OmitPropertyTypes;
  use RewriteNullCoalesceAssignment, RewriteLambdaExpressions, RewriteClassOnObjects;

  /** Sets up type => literal mappings */
  public function __construct() {
    $this->literals= [
      IsUnion::class    => function($t) { return null; },
      IsFunction::class => function($t) { return null; },
      IsArray::class    => function($t) { return 'array'; },
      IsMap::class      => function($t) { return 'array'; },
      IsValue::class    => function($t) { return $t->literal(); },
      IsNullable::class => function($t) { $l= $this->literal($t->element); return null === $l ? null : '?'.$l; },
      IsLiteral::class  => function($t) {
        $l= $t->literal();
        return 'mixed' === $l ? null : $l;
      },
    ];
  }
}