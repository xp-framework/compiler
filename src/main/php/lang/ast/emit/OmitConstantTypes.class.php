<?php namespace lang\ast\emit;

/**
 * Removes type hints for PHP versions that do not support typed
 * constants - everything below PHP 8.3
 *
 * @see  https://wiki.php.net/rfc/typed_class_constants
 */
trait OmitConstantTypes {

  /**
   * Returns constant type
   *
   * @param  ?lang.ast.Type $type
   * @return string
   */
  protected function constantType($type) { return ''; }
}