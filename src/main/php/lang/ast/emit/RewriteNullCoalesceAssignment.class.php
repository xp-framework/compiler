<?php namespace lang\ast\emit;

/**
 * Rewrites the null-coalesce equal operator `??=` using the regular
 * assignment operator.
 *
 * @see  https://wiki.php.net/rfc/null_coalesce_equal_operator
 */
trait RewriteNullCoalesceAssignment {

  protected function emitAssignment($result, $assignment) {
    if ('??=' === $assignment->operator) {
      $this->emitAssign($result, $assignment->variable);
      $result->out->write('??');
      $this->emitOne($result, $assignment->variable);
      $result->out->write('=');
      $this->emitOne($result, $assignment->expression);
    } else {
      parent::emitAssignment($result, $assignment);
    }
  }
}