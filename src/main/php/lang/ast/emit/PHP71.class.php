<?php namespace lang\ast\emit;

/**
 * PHP 7.1 syntax
 *
 * @see  https://wiki.php.net/rfc/nullable_types - Not yet implemented!
 * @see  https://wiki.php.net/rfc/short_list_syntax - Not yet implemented!
 * @see  https://wiki.php.net/rfc/class_const_visibility - Not yet implemented!
 * @see  https://wiki.php.net/rfc/multiple-catch
 * @see  https://wiki.php.net/rfc/void_return_type
 * @see  https://wiki.php.net/rfc/iterable
 */
class PHP71 extends \lang\ast\Emitter {

  private function type($name) {
    static $unsupported= ['object' => 72];

    return isset($unsupported[$name]) ? null : $name;
  }

  protected function paramType($name) {
    return $this->type($name);
  }

  protected function returnType($name) {
    return $this->type($name);
  }
}