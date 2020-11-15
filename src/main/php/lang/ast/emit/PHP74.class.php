<?php namespace lang\ast\emit;

use lang\ast\types\{IsUnion, IsFunction, IsArray, IsMap, IsNullable};

/**
 * PHP 7.4 syntax
 *
 * @see  https://wiki.php.net/rfc#php_74
 */
class PHP74 extends PHP {
  use RewriteBlockLambdaExpressions, RewriteClassOnObjects;

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