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
      $this->enclose($result, $lambda->body, $lambda->signature, function($result, $body) {
        $this->emitAll($result, $body->statements);
      });
    } else if (is_array($lambda->body)) { // BC for xp-framework/ast <= 7.0
      $this->enclose($result, $lambda, $lambda->signature, function($result, $lambda) {
        $this->emitAll($result, $lambda->body);
      });
    } else {
      $result->out->write('fn');
      $this->emitSignature($result, $lambda->signature);
      $result->out->write('=>');
      $this->emitOne($result, $lambda->body);
    }
  }
}