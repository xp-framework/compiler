<?php namespace lang\ast\emit;

/**
 * When property hooks were introduced into PHP 8.4, it also added a mechanism
 * for properties to be declared as final. For other PHP versions, emit without
 * final modifier, store `final` in xp::$meta
 *
 * @see  https://wiki.php.net/rfc/property-hooks
 * @test lang.ast.unittest.emit.MembersTest
 */
trait FinalProperties {

  protected function emitProperty($result, $property) {
    $property->modifiers= array_diff($property->modifiers, ['final']);
    parent::emitProperty($result, $property);
    $result->codegen->scope[0]->meta[self::PROPERTY][$property->name][DETAIL_ARGUMENTS]= [MODIFIER_FINAL];
  }
}