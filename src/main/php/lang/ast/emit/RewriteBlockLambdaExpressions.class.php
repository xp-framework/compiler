<?php namespace lang\ast\emit;

use lang\ast\nodes\Block;

/**
 * Rewrites lambda expressions with multi-statement bodies to regular
 * closures.
 *
 * @see  https://wiki.php.net/rfc/arrow_functions_v2#multi-statement_bodies
 */
trait RewriteBlockLambdaExpressions {

  protected function emitLambda($result, $lambda) {
    if ($lambda->body instanceof Block) {
      $this->enclose($result, $lambda->body, $lambda->signature, isset($lambda->static) && $lambda->static, function($result, $body) {
        $this->emitAll($result, $body->statements);
      });
    } else {
      parent::emitLambda($result, $lambda);
    }
  }
}