<?php namespace lang\ast\emit;

use lang\ast\Emitter;

/**
 * PHP 7.4 syntax
 *
 * @see  https://wiki.php.net/rfc/typed_properties_v2
 */
class PHP74 extends Emitter {
  protected $unsupported= [
    'mixed'    => null,
  ];

  protected function emitProperty($property) {
    $this->meta[0][self::PROPERTY][$property->name]= [
      DETAIL_RETURNS     => $property->type ? $property->type->name() : 'var',
      DETAIL_ANNOTATIONS => $property->annotations ? $property->annotations : [],
      DETAIL_COMMENT     => $property->comment,
      DETAIL_TARGET_ANNO => [],
      DETAIL_ARGUMENTS   => []
    ];

    $this->out->write(implode(' ', $property->modifiers).' '.$this->type($property->type).' $'.$property->name);
    if (isset($property->expression)) {
      $this->out->write('=');
      $this->emit($property->expression);
    }
    $this->out->write(';');
  }
}