<?php namespace lang\ast\syntax\php;

use lang\ast\nodes\{Assignment, IfStatement, InstanceExpression, InstanceOfExpression, InvokeExpression, Literal, TryStatement, UsingStatement, Variable};
use lang\ast\syntax\Extension;

/**
 * Using statement
 *
 * ```php
 * // Syntax
 * using ($f= new File()) {
 *   $f->open(File::WRITE);
 *   $f->write(...);
 * }
 *
 * // Rewritten to
 * $f= new File();
 * try {
 *   $f->open(File::WRITE);
 *   $f->write(...);
 * } finally {
 *   $f->close();
 * }
 * ```
 *
 * @see  https://github.com/xp-framework/compiler/pull/33
 * @test xp://lang.ast.unittest.emit.UsingTest
 */
class Using implements Extension {

  public function setup($language, $emitter) {
    $language->stmt('using', function($parse, $node) {
      $parse->expecting('(', 'using arguments');
      $arguments= $this->expressions($parse, ')');
      $parse->expecting(')', 'using arguments');

      $parse->expecting('{', 'using block');
      $statements= $this->statements($parse);
      $parse->expecting('}', 'using block');

      return new UsingStatement($arguments, $statements);
    });

    $emitter->transform('using', function($codegen, $node) {
      $cleanup= [];
      foreach ($node->arguments as $expression) {
        switch ($expression->kind) {
          case 'variable': $variable= $expression; yield $expression; break;
          case 'assignment': $variable= $expression->variable; yield $expression; break;
          default: $variable= new Variable($codegen->symbol()); yield new Assignment($variable, '=', $expression); break;
        }

        $cleanup[]= new IfStatement(new InstanceOfExpression($variable, '\lang\Closeable'),
          [new InvokeExpression(new InstanceExpression($variable, new Literal('close')), [])],
          [new IfStatement(new InstanceOfExpression($variable, '\IDisposable'),
            [new InvokeExpression(new InstanceExpression($variable, new Literal('__dispose')), [])]
          )]
        );
        $cleanup[]= new InvokeExpression(new Literal('unset'), [$variable]);
      }

      yield new TryStatement($node->body, null, $cleanup);
    });
  }
}