<?php namespace lang\ast\emit;

/**
 * Rewrites the null-coalesce equal operator `??=` using the regular
 * assignment operator.
 *
 * @see  https://wiki.php.net/rfc/null_coalesce_equal_operator
 */
trait RewriteNullCoalesceAssignment {

  protected function emitAssignment($assignment) {
    if ('??=' === $assignment->operator) {
      $this->emitAssign($assignment->variable);
      $this->out->write('=');
      $this->emit($assignment->variable);
      $this->out->write('??');
      $this->emit($assignment->expression);
    } else {
      parent::emitAssignment($assignment);
    }
  }
}