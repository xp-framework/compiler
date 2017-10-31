<?php namespace lang\ast\unittest\emit;

use lang\XPClass;

/**
 * Traits
 *
 * @see   http://php.net/traits
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

  #[@test]
  public function trait_is_resolved() {
    $t= $this->type('use lang\ast\unittest\emit\Loading; class <T> { use Loading; }');
    $this->assertEquals([new XPClass(Loading::class)], $t->getTraits());
  }

  #[@test]
  public function trait_method_aliased() {
    $t= $this->type('use lang\ast\unittest\emit\Loading; class <T> {
      use Loading {
        loaded as hasLoaded;
      }
    }');
    $this->assertTrue($t->hasMethod('hasLoaded'));
  }

  #[@test]
  public function trait_method_aliased_qualified() {
    $t= $this->type('use lang\ast\unittest\emit\Loading; class <T> {
      use Loading {
        Loading::loaded as hasLoaded;
      }
    }');
    $this->assertTrue($t->hasMethod('hasLoaded'));
  }
}