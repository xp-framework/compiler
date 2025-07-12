<?php namespace lang\ast\emit;

use lang\ast\nodes\Literal;

/**
 * Rewrites `clone(...)` and partial function applications
 *
 * @see  https://wiki.php.net/rfc/clone_with_v2
 * @see  https://wiki.php.net/rfc/partial_function_application_v2
 */
trait RewriteCallables {
  use RewritePartialFunctionApplications { emitCallable as emitPartial; }

  protected function emitCallable($result, $callable) {
    if ($callable->expression instanceof Literal && 'clone' === $callable->expression->expression) {
      $result->out->write('fn($o) => clone $o');
    } else {
      $this->emitPartial($result, $callable);
    }
  }
}