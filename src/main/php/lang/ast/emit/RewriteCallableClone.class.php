<?php namespace lang\ast\emit;

use lang\ast\nodes\Literal;

/** @see https://wiki.php.net/rfc/clone_with_v2 */
trait RewriteCallableClone {

  protected function emitCallable($result, $callable) {
    if ($callable->expression instanceof Literal && 'clone' === $callable->expression->expression) {
      $result->out->write('fn($o) => clone $o');
    } else {
      parent::emitCallable($result, $callable);
    }
  }
}