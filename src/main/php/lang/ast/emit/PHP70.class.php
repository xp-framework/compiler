<?php namespace lang\ast\emit;

class PHP70 extends \lang\ast\Emitter {

  protected function type($name) {
    static $unsupported= ['void' => 71, 'iterable' => 71];

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