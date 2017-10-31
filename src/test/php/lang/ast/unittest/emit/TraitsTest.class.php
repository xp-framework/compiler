<?php namespace lang\ast\unittest\emit;

use lang\XPClass;

/**
 * Traits
 *
 * @see   https://wiki.php.net/rfc/horizontalreuse
 */
class TraitsTest extends EmittingTest {

  #[@test]
  public function trait_is_included() {
    $t= $this->type('class <T> { use \lang\ast\unittest\emit\Loading; }');
    $this->assertEquals([new XPClass(Loading::class)], $t->getTraits());
  }

  #[@test]
  public function trait_method_is_part_of_type() {
    $t= $this->type('class <T> { use \lang\ast\unittest\emit\Loading; }');
    $this->assertTrue($t->hasMethod('loaded'));
  }
}