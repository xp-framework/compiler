<?php namespace lang\ast\syntax\php;
   
use lang\ast\nodes\LambdaExpression;
use lang\ast\nodes\Parameter;
use lang\ast\nodes\Signature;

/**
 * Hack-style arrow functions
 *
 * @deprecated See https://github.com/xp-framework/compiler/issues/65
 * @see  https://docs.hhvm.com/hack/functions/anonymous-functions
 * @test xp://lang.ast.unittest.emit.LambdasTest
 */
class HackArrowFunctions {

  public function setup($parser, $emitter) {
    $parser->infix('==>', 80, function($parse, $node, $left) {
      $parse->warn('Hack language style arrow functions are deprecated, please use `fn` syntax instead');

      $signature= new Signature([new Parameter($left->name, null)], null);
      if ('{' === $parse->token->value) {
        $parse->forward();
        $statements= $this->statements($parse);
        $parse->expecting('}', 'arrow function');
      } else {
        $statements= $this->expressionWithThrows($parse, 0);
      }

      return new LambdaExpression($signature, $statements, $node->line);
    });
  }
}