<?php namespace lang\ast\emit;

/**
 * Omits public / protected / private modifiers from class constants for
 * PHP versions not supporting them (all versions below PHP 7.1).
 *
 * @see  https://wiki.php.net/rfc/class_const_visibility
 */
trait OmitConstModifiers {

  protected function emitConst($const) {
    $this->out->write('const '.$const->name.'=');
    $this->emit($const->expression);
    $this->out->write(';');
  }
}
