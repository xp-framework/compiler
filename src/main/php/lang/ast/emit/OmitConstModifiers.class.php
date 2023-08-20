<?php namespace lang\ast\emit;

/**
 * Omits public / protected / private modifiers from class constants for
 * PHP versions not supporting them (all versions below PHP 7.1).
 *
 * @see  https://wiki.php.net/rfc/class_const_visibility
 */
trait OmitConstModifiers {

  protected function emitConst($result, $const) {
    $result->codegen->scope[0]->meta[self::CONSTANT][$const->name]= [
      DETAIL_RETURNS     => $const->type ? $const->type->name() : 'var',
      DETAIL_ANNOTATIONS => $const->annotations,
      DETAIL_COMMENT     => $const->comment,
      DETAIL_TARGET_ANNO => [],
      DETAIL_ARGUMENTS   => []
    ];

    $result->out->write("const {$const->name}=");
    $this->emitOne($result, $const->expression);
    $result->out->write(';');
  }
}