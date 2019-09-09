<?php namespace lang\ast\syntax\php;

use lang\ast\nodes\Assignment;
use lang\ast\nodes\BinaryExpression;
use lang\ast\nodes\Braced;
use lang\ast\nodes\InstanceExpression;
use lang\ast\nodes\Literal;
use lang\ast\nodes\TernaryExpression;
use lang\ast\nodes\Variable;
use lang\ast\syntax\Extension;

/**
 * Null-safe instance operator
 *
 * ```php
 * // Syntax
 * $value= $instance?->method();
 *
 * // Rewritten to
 * $value= null === ($_N0= $instance) ? null : $_N0->method()
 * ```
 *
 * @see  https://github.com/xp-framework/compiler/issues/9
 * @test xp://lang.ast.unittest.emit.NullSafeTest
 */
class NullSafe implements Extension {

  public function setup($language, $emitter) {
    $language->infix('?->', 80, function($parse, $node, $left) {
      if ('{' === $parse->token->value) {
        $parse->forward();
        $expr= $this->expression($parse, 0);
        $parse->expecting('}', 'dynamic member');
      } else {
        $expr= new Literal($parse->token->value);
        $parse->forward();
      }

      $value= new InstanceExpression($left, $expr, $node->line);
      $value->kind= 'nullsafeinstance';
      return $value;
    });

    $emitter->transform('nullsafeinstance', function($codegen, $node) {
      static $i= 0;

      $temp= new Variable('_N'.($i++));
      $null= new Literal('null');
      return new TernaryExpression(
        new BinaryExpression($null, '===', new Braced(new Assignment($temp, '=', $node->expression))),
        $null,
        new InstanceExpression($temp, $node->member)
      );
    });
  }
}