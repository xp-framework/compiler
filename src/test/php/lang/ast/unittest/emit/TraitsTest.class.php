<?php namespace lang\ast\unittest\emit;

use lang\XPClass;
use unittest\Assert;

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
    Assert::equals([new XPClass(Loading::class)], $t->getTraits());
  }

  #[@test]
  public function trait_method_is_part_of_type() {
    $t= $this->type('class <T> { use \lang\ast\unittest\emit\Loading; }');
    Assert::true($t->hasMethod('loaded'));
  }

  #[@test]
  public function trait_is_resolved() {
    $t= $this->type('use lang\ast\unittest\emit\Loading; class <T> { use Loading; }');
    Assert::equals([new XPClass(Loading::class)], $t->getTraits());
  }

  #[@test]
  public function trait_method_aliased() {
    $t= $this->type('use lang\ast\unittest\emit\Loading; class <T> {
      use Loading {
        loaded as hasLoaded;
      }
    }');
    Assert::true($t->hasMethod('hasLoaded'));
  }

  #[@test]
  public function trait_method_aliased_qualified() {
    $t= $this->type('use lang\ast\unittest\emit\Loading; class <T> {
      use Loading {
        Loading::loaded as hasLoaded;
      }
    }');
    Assert::true($t->hasMethod('hasLoaded'));
  }
}