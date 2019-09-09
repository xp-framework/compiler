<?php namespace lang\ast\syntax\php;

use lang\ast\nodes\ThrowExpression;
use lang\ast\syntax\Extension;

/**
 * Throw expressions
 *
 * ```php
 * // Syntax
 * return $user ?? throw new IllegalStateException('No such user');
 *
 * // Rewritten to
 * return $user ?? (function() { throw ... })();
 * ```
 * @see  https://github.com/xp-framework/compiler/pull/53
 * @test xp://lang.ast.unittest.emit.ExceptionsTest
 */
class ThrowExpressions implements Extension {

  public function setup($language, $emitter) {
    $language->prefix('throw', 0, function($parse, $token) {
      return new ThrowExpression($this->expression($parse, 0), $token->line);
    });
  }
}