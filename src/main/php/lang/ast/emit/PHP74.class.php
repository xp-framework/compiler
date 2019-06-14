<?php namespace lang\ast\emit;

use lang\ast\ArrayType;
use lang\ast\Emitter;
use lang\ast\FunctionType;
use lang\ast\MapType;
use lang\ast\Type;
use lang\ast\UnionType;

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

    // See https://wiki.php.net/rfc/typed_properties_v2#supported_types
    if (null === $property->type || $property->type instanceof UnionType || $property->type instanceof FunctionType) {
      $hint= '';
    } else if ($property->type instanceof ArrayType || $property->type instanceof MapType) {
      $hint= 'array';
    } else if ($property->type instanceof Type && 'callable' !== $property->type->literal() && 'void' !== $property->type->literal()) {
      $hint= $property->type->literal();
    } else {
      $hint= '';
    }

    $this->out->write(implode(' ', $property->modifiers).' '.$hint.' $'.$property->name);
    if (isset($property->expression)) {
      $this->out->write('=');
      $this->emit($property->expression);
    }
    $this->out->write(';');
  }
}