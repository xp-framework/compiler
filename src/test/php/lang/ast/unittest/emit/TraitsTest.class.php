<?php namespace lang\ast\unittest\emit;

use lang\Reflection;
use test\verify\Runtime;
use test\{Action, Assert, Test};

/**
 * Traits
 *
 * @see   http://php.net/traits
 * @see   https://wiki.php.net/rfc/horizontalreuse
 */
class TraitsTest extends EmittingTest {

  #[Test]
  public function trait_is_included() {
    $t= $this->declare('class %T { use \lang\ast\unittest\emit\Loading; }');
    Assert::equals([Reflection::type(Loading::class)], $t->traits());
  }

  #[Test]
  public function trait_method_is_part_of_type() {
    $t= $this->declare('class %T { use \lang\ast\unittest\emit\Loading; }');
    Assert::notEquals(null, $t->method('loaded'));
  }

  #[Test]
  public function trait_is_resolved() {
    $t= $this->declare('use lang\ast\unittest\emit\Loading; class %T { use Loading; }');
    Assert::equals([Reflection::type(Loading::class)], $t->traits());
  }

  #[Test]
  public function trait_method_aliased() {
    $t= $this->declare('use lang\ast\unittest\emit\Loading; class %T {
      use Loading {
        loaded as hasLoaded;
      }
    }');
    Assert::notEquals(null, $t->method('hasLoaded'));
  }

  #[Test]
  public function trait_method_aliased_qualified() {
    $t= $this->declare('use lang\ast\unittest\emit\Loading; class %T {
      use Loading {
        Loading::loaded as hasLoaded;
      }
    }');
    Assert::notEquals(null, $t->method('hasLoaded'));
  }

  #[Test]
  public function trait_method_insteadof() {
    $t= $this->declare('use lang\ast\unittest\emit\{Loading, Spinner}; class %T {
      use Loading, Spinner {
        Spinner::loaded as noLongerSpinning;
        Loading::loaded insteadof Spinner;
      }
    }');
    $instance= $t->newInstance();
    Assert::equals('Loaded', $t->method('loaded')->invoke($instance));
    Assert::equals('Not spinning', $t->method('noLongerSpinning')->invoke($instance));
  }

  #[Test, Runtime(php: '>=8.2.0-dev')]
  public function can_have_constants() {
    $t= $this->declare('trait %T { const FIXTURE = 1; }');
    Assert::equals(1, $t->constant('FIXTURE')->value());
  }
}