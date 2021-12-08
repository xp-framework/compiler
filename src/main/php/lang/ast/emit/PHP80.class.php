<?php namespace lang\ast\emit;

use lang\ast\Node;
use lang\ast\types\{IsUnion, IsIntersection, IsFunction, IsArray, IsMap, IsNullable, IsValue, IsLiteral};

/**
 * PHP 8.0 syntax
 *
 * @see  https://wiki.php.net/rfc#php_80
 */
class PHP80 extends PHP {
  use RewriteBlockLambdaExpressions, RewriteExplicitOctals, RewriteEnums, ReadonlyProperties, CallablesAsClosures;

  /** Sets up type => literal mappings */
  public function __construct() {
    $this->literals= [
      IsArray::class        => function($t) { return 'array'; },
      IsMap::class          => function($t) { return 'array'; },
      IsFunction::class     => function($t) { return 'callable'; },
      IsValue::class        => function($t) { return $t->literal(); },
      IsNullable::class     => function($t) { $l= $this->literal($t->element); return null === $l ? null : '?'.$l; },
      IsIntersection::class => function($t) { return null; },
      IsUnion::class        => function($t) {
        $u= '';
        foreach ($t->components as $component) {
          if (null === ($l= $this->literal($component))) return null;
          $u.= '|'.$l;
        }
        return substr($u, 1);
      },
      IsLiteral::class      => function($t) {
        $l= $t->literal();
        return 'never' === $l ? 'void' : $l;
      }
    ];
  }
}