<?php namespace lang\ast\emit;

/**
 * Rewrites lambda expressions with multi-statement bodies to regular
 * closures.
 *
 * @see  https://wiki.php.net/rfc/arrow_functions_v2#multi-statement_bodies
 */
trait RewriteBlockLambdaExpressions {
  use RewriteLambdaExpressions { emitLambda as rewriteLambda; }

  protected function emitLambda($lambda) {
    if (is_array($lambda->body)) {
      $this->rewriteLambda($lambda);
    } else {
      parent::emitLambda($lambda);
    }
  }
}