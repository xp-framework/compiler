<?php namespace lang\ast\emit;

/**
 * PHP 7.2 syntax
 *
 * @see  https://wiki.php.net/rfc/object-typehint
 */
class PHP72 extends \lang\ast\Emitter {

  protected function type($name) {
    return $name;
  }

  protected function catches($catch) {
    $this->out->write('catch('.implode('|', $catch[0]).' $'.$catch[1].') {');
    $this->emit($catch[2]);
    $this->out->write('}');
  }
}