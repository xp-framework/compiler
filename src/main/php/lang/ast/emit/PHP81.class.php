<?php namespace lang\ast\emit;

use lang\ast\Node;
use lang\ast\types\{IsUnion, IsIntersection, IsFunction, IsArray, IsMap, IsNullable, IsValue, IsLiteral};

/**
 * PHP 8.1 syntax
 *
 * @test lang.ast.unittest.emit.PHP81Test
 * @see  https://wiki.php.net/rfc#php_81
 */
class PHP81 extends PHP {
  use RewriteBlockLambdaExpressions, ReadonlyClasses;

  /** Sets up type => literal mappings */
  public function __construct() {
    $this->literals= [
      IsArray::class        => function($t) { return 'array'; },
      IsMap::class          => function($t) { return 'array'; },
      IsFunction::class     => function($t) { return 'callable'; },
      IsValue::class        => function($t) { return $t->literal(); },
      IsNullable::class     => function($t) { $l= $this->literal($t->element); return null === $l ? null : '?'.$l; },
      IsIntersection::class => function($t) {
        $i= '';
        foreach ($t->components as $component) {
          if (null === ($l= $this->literal($component))) return null;
          $i.= '&'.$l;
        }
        return substr($i, 1);
      },
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
          'true'     => 'bool',
          'false'    => 'bool',
        ];

        $l= $t->literal();
        return (1 === ($r= $rewrite[$l] ?? $l)) ? null : $r;
      }
    ];
  }
}