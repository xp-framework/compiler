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

  // See https://wiki.php.net/rfc/typed_properties_v2#supported_types
  protected function propertyType($type) {
    if (null === $type || $type instanceof UnionType || $type instanceof FunctionType) {
      return '';
    } else if ($type instanceof ArrayType || $type instanceof MapType) {
      return 'array';
    } else if ($type instanceof Type && 'callable' !== $type->literal() && 'void' !== $type->literal()) {
      return $type->literal();
    } else {
      return '';
    }
  }

  protected function emitLambda($lambda) {
    if (is_array($lambda->body)) return parent::emitLambda($lambda);

    $this->out->write('fn');
    $this->emitSignature($lambda->signature);
    $this->out->write('=>');
    $this->emit($lambda->body);
  }
}