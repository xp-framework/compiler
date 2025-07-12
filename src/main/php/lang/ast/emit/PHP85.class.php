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
 * PHP 8.5 syntax
 *
 * @test lang.ast.unittest.emit.PHP85Test
 * @see  https://wiki.php.net/rfc#php_85
 */
class PHP85 extends PHP {
  use RewriteBlockLambdaExpressions, RewriteCallables, RewriteCloneWith; // TODO: Remove once PR is merged!

  public $targetVersion= 80500;

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
          if (null === ($l= $this->literal($component))) return null;
          $u.= '|'.$l;
        }
        return substr($u, 1);
      },
      IsLiteral::class      => function($t) { return $t->literal(); },
      IsGeneric::class      => function($t) { return null; }
    ];
  }
}