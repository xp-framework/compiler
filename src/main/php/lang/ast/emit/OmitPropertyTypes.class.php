<?php namespace lang\ast\emit;

/**
 * Removes type hints for PHP versions and generates virtual instance
 * properties for PHP runtimes that that do not support typed properties,
 * which is everything below PHP 7.4.
 *
 * @see  https://bugs.php.net/bug.php?id=52225
 * @see  https://github.com/xp-framework/rfc/issues/340
 * @see  https://wiki.php.net/rfc/typed_properties_v2
 */
trait OmitPropertyTypes {

  /**
   * Returns property type
   *
   * @param  lang.ast.Type $type
   * @return string
   */
  protected function propertyType($type) { return ''; }

  protected function emitProperty($result, $property) {
    if (null === $property->type || in_array('static', $property->modifiers)) {
      return parent::emitProperty($result, $property);
    }

    // Only track meta information, push property to virtual locals
    $result->meta[0][self::PROPERTY][$property->name]= [
      DETAIL_RETURNS     => $property->type ? $property->type->name() : 'var',
      DETAIL_ANNOTATIONS => $property->annotations,
      DETAIL_COMMENT     => $property->comment,
      DETAIL_TARGET_ANNO => [],
      DETAIL_ARGUMENTS   => []
    ];
    $result->locals[2][]= $property;
  }
}