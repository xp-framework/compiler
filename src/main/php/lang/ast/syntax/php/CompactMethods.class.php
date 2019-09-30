<?php namespace lang\ast\syntax\php;

use lang\ast\Node;
use lang\ast\nodes\{Method, ReturnStatement};
use lang\ast\syntax\Extension;

/**
 * Compact functions
 *
 * ```php
 * // Syntax
 * class Person {
 *   private string $name;
 *   public function name(): string ==> $this->name;
 * }
 *
 * // Rewritten to
 * class Person {
 *   private string $name;
 *   public function name(): string { return $this->name; }
 * }
 * ```
 * @see  https://github.com/xp-framework/rfc/issues/241
 * @test xp://lang.ast.unittest.emit.CompactFunctionsTest
 */
class CompactMethods implements Extension {

  public function setup($parser, $emitter) {
    $parser->body('fn', function($parse, &$body, $annotations, $modifiers) {
      $line= $parse->token->line;
      $comment= $parse->comment;
      $parse->comment= null;

      $parse->forward();
      $name= $parse->token->value;
      $lookup= $name.'()';
      if (isset($body[$lookup])) {
        $parse->raise('Cannot redeclare method '.$lookup);
      }

      $parse->forward();
      $signature= $this->signature($parse);

      $parse->expecting('=>', 'compact function');
      $return= new ReturnStatement($this->expression($parse, 0), $parse->token->line);
      $parse->expecting(';', 'compact function');

      $body[$lookup]= new Method($modifiers, $name, $signature, [$return], $annotations, $comment, $line);
    });
  }
}