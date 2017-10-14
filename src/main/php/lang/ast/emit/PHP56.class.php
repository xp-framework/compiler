<?php namespace lang\ast\emit;

/**
 * PHP 5.6 syntax
 *
 * @see  https://wiki.php.net/rfc/pow-operator
 * @see  https://wiki.php.net/rfc/variadics
 * @see  https://wiki.php.net/rfc/argument_unpacking
 * @see  https://wiki.php.net/rfc/use_function - Not yet implemented
 */
class PHP56 extends \lang\ast\Emitter {

  protected function type($name) {
    static $unsupported= [
      'void'     => 71,
      'iterable' => 71,
      'string'   => 70,
      'int'      => 70,
      'bool'     => 70,
      'float'    => 70
    ];

    return isset($unsupported[$name]) ? null : $name;
  }

  protected function catches($catch) {
    $last= array_pop($catch[0]);
    $label= 'c'.crc32($last);
    foreach ($catch[0] as $type) {
      $this->out->write('catch('.$type.' $'.$catch[1].') { goto '.$label.'; }');
    }

    $this->out->write('catch('.$last.' $'.$catch[1].') { '.$label.':');
    $this->emit($catch[2]);
    $this->out->write('}');
  }
}