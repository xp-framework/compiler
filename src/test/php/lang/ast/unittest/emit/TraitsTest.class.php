<?php namespace lang\ast\unittest\emit;

use lang\XPClass;
use unittest\{Assert, Test};

/**
 * Traits
 *
 * @see   http://php.net/traits
 * @see   https://wiki.php.net/rfc/horizontalreuse
 */
class TraitsTest extends EmittingTest {

  #[Test]
  public function trait_is_included() {
    $t= $this->type('class <T> { use \lang\ast\unittest\emit\Loading; }');
    Assert::equals([new XPClass(Loading::class)], $t->getTraits());
  }

  #[Test]
  public function trait_method_is_part_of_type() {
    $t= $this->type('class <T> { use \lang\ast\unittest\emit\Loading; }');
    Assert::true($t->hasMethod('loaded'));
  }

  #[Test]
  public function trait_is_resolved() {
    $t= $this->type('use lang\ast\unittest\emit\Loading; class <T> { use Loading; }');
    Assert::equals([new XPClass(Loading::class)], $t->getTraits());
  }

  #[Test]
  public function trait_method_aliased() {
    $t= $this->type('use lang\ast\unittest\emit\Loading; class <T> {
      use Loading {
        loaded as hasLoaded;
      }
    }');
    Assert::true($t->hasMethod('hasLoaded'));
  }

  #[Test]
  public function trait_method_aliased_qualified() {
    $t= $this->type('use lang\ast\unittest\emit\Loading; class <T> {
      use Loading {
        Loading::loaded as hasLoaded;
      }
    }');
    Assert::true($t->hasMethod('hasLoaded'));
  }

  #[Test]
  public function trait_method_insteadof() {
    $t= $this->type('use lang\ast\unittest\emit\{Loading, Spinner}; class <T> {
      use Loading, Spinner {
        Spinner::loaded as noLongerSpinning;
        Loading::loaded insteadof Spinner;
      }
    }');
    $instance= $t->newInstance();
    Assert::equals('Loaded', $t->getMethod('loaded')->invoke($instance));
    Assert::equals('Not spinning', $t->getMethod('noLongerSpinning')->invoke($instance));
  }
}