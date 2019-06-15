<?php namespace lang\ast\emit;

/**
 * Removes type hints for PHP versions that do not support return
 * types - everything below PHP 7.0
 *
 * @see  https://wiki.php.net/rfc/return_types
 */
trait OmitReturnTypes {

  /**
   * Returns property type
   *
   * @param  lang.ast.Type $type
   * @return string
   */
  protected function returnType($type) { return ''; }
}