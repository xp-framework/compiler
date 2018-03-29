<?php namespace lang\ast\emit;

/**
 * PHP 7.2 syntax
 *
 * @see  https://wiki.php.net/rfc/object-typehint
 */
class PHP72 extends \lang\ast\Emitter {
  protected $unsupported= [
    'mixed'    => null,
  ];
}