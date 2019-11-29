<?php namespace lang\ast\syntax\php;

use lang\ast\nodes\{Assignment, BinaryExpression, Braced, InstanceExpression, Literal, TernaryExpression, Variable};
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
      $temp= new Variable($codegen->symbol());
      $null= new Literal('null');
      return new TernaryExpression(
        new BinaryExpression($null, '===', new Braced(new Assignment($temp, '=', $node->expression))),
        $null,
        new InstanceExpression($temp, $node->member)
      );
    });
  }
}