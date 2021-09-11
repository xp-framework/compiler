<?php namespace lang\ast\emit;

/**
 * Creates __get() and __set() overloads for readonly properties
 *
 * @see  https://github.com/xp-framework/compiler/issues/115
 * @see  https://wiki.php.net/rfc/readonly_properties_v2
 */
trait ReadonlyProperties {

  protected function emitProperty($result, $property) {
    $p= array_search('readonly', $property->modifiers);
    if (false === $p) return parent::emitProperty($result, $property);

    $result->meta[0][self::PROPERTY][$property->name]= [
      DETAIL_RETURNS     => $property->type ? $property->type->name() : 'var',
      DETAIL_ANNOTATIONS => $property->annotations,
      DETAIL_COMMENT     => $property->comment,
      DETAIL_TARGET_ANNO => [],
      DETAIL_ARGUMENTS   => [MODIFIER_READONLY]
    ];
    $result->locals[2][$property->name]= null;

    if (isset($property->expression)) {
      if ($this->isConstant($result, $property->expression)) {
        $result->out->write('=');
        $this->emitOne($result, $property->expression);
      } else if (in_array('static', $property->modifiers)) {
        $result->locals[0]['self::$'.$property->name]= $property->expression;
      } else {
        $result->locals[1]['$this->'.$property->name]= $property->expression;
      }
    }
  }
}