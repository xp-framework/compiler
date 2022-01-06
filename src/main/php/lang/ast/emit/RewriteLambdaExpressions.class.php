<?php namespace lang\ast\emit;

use lang\ast\nodes\Block;

/**
 * Rewrites lambda expressions (or "arrow functions") to regular closures.
 *
 * @see  https://wiki.php.net/rfc/arrow_functions_v2
 */
trait RewriteLambdaExpressions {

  protected function emitLambda($result, $lambda) {
    $this->enclose($result, $lambda, $lambda->signature, isset($lambda->static) && $lambda->static, function($result, $lambda) {
      if ($lambda->body instanceof Block) {
        $this->emitAll($result, $lambda->body->statements);
      } else {
        $result->out->write('return ');
        $this->emitOne($result, $lambda->body);
        $result->out->write(';');
      }
    });
  }
}