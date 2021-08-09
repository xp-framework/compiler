<?php namespace lang\ast\emit;

use lang\ast\types\{IsUnion, IsIntersection, IsFunction, IsArray, IsMap, IsNullable, IsValue, IsLiteral};

trait PHP71Literals {

  /** Sets up type => literal mappings */
  public function __construct() {
    $this->literals= [
      IsFunction::class     => function($t) { return 'callable'; },
      IsArray::class        => function($t) { return 'array'; },
      IsMap::class          => function($t) { return 'array'; },
      IsValue::class        => function($t) { $l= $t->literal(); return 'static' === $l ? 'self' : $l; },
      IsNullable::class     => function($t) { $l= $this->literal($t->element); return null === $l ? null : '?'.$l; },
      IsUnion::class        => function($t) { return null; },
      IsIntersection::class => function($t) { return null; },
      IsLiteral::class      => function($t) {
        $l= $t->literal();
        return ('object' === $l || 'mixed' === $l) ? null : ('never' === $l ? 'void' : $l);
      },
    ];
  }
}