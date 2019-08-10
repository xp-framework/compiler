<?php namespace lang\ast\emit;

/**
 * Removes type hints for PHP versions that do not support typed
 * properties - everything below PHP 7.4
 *
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
}