<?php namespace lang\ast\emit;

use lang\ast\types\{
  IsArray,
  IsFunction,
  IsGeneric,
  IsIntersection,
  IsLiteral,
  IsMap,
  IsNullable,
  IsUnion,
  IsValue
};

/**
 * PHP 8.0 syntax
 *
 * @see  https://wiki.php.net/rfc#php_80
 */
class PHP80 extends PHP {
  use RewriteBlockLambdaExpressions, RewriteDynamicClassConstants, RewriteExplicitOctals, RewriteEnums;
  use ReadonlyClasses, ReadonlyProperties, CallablesAsClosures, ArrayUnpackUsingMerge;

  /** Sets up type => literal mappings */
  public function __construct() {
    $this->literals= [
      IsArray::class        => function($t) { return 'array'; },
      IsMap::class          => function($t) { return 'array'; },
      IsFunction::class     => function($t) { return 'callable'; },
      IsValue::class        => function($t) { return $t->literal(); },
      IsNullable::class     => function($t) {
        if (null === ($l= $this->literal($t->element))) return null;
        return $t->element instanceof IsUnion ? $l.'|null' : '?'.$l;
      },
      IsIntersection::class => function($t) { return null; },
      IsUnion::class        => function($t) {
        $u= '';
        foreach ($t->components as $component) {
          if ('null' === $component->literal) {
            $u.= '|null';
          } else if (null !== ($l= $this->literal($component))) {
            $u.= '|'.$l;
          } else {
            return null;  // One of the components didn't resolve
          }
        }
        return substr($u, 1);
      },
      IsLiteral::class      => function($t) {
        static $rewrite= [
          'null'     => 1,
          'never'    => 'void',
          'true'     => 'bool',
          'false'    => 'bool',
        ];

        $l= $t->literal();
        return (1 === ($r= $rewrite[$l] ?? $l)) ? null : $r;
      },
      IsGeneric::class      => function($t) { return null; }
    ];
  }
}