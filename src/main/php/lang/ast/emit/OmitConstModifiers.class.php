<?php namespace lang\ast\emit;

/**
 * Omits public / protected / private modifiers from class constants for
 * PHP versions not supporting them (all versions below PHP 7.1).
 *
 * @see  https://wiki.php.net/rfc/class_const_visibility
 */
trait OmitConstModifiers {

  protected function emitConst($result, $const) {
    $result->out->write('const '.$const->name.'=');
    $this->emit($result, $const->expression);
    $result->out->write(';');
  }
}
