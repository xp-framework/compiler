<?php namespace lang\ast\unittest\emit;

/**
 * Argument promotion
 *
 * @see  https://github.com/xp-framework/rfc/issues/240
 * @see  https://docs.hhvm.com/hack/other-features/constructor-parameter-promotion
 * @see  https://wiki.php.net/rfc/automatic_property_initialization (Declined)
 */
class ArgumentPromotionTest extends EmittingTest {

  #[@test]
  public function in_constructor() {
    $r= $this->run('class <T> {
      public function __construct(private $id= "test") {
        // Empty
      }

      public function run() {
        return $this->id;
      }
    }');
    $this->assertEquals('test', $r);
  }

  #[@test]
  public function can_be_used_in_constructor() {
    $r= $this->run('class <T> {
      public function __construct(private $id= "test") {
        $this->id.= "ed";
      }

      public function run() {
        return $this->id;
      }
    }');
    $this->assertEquals('tested', $r);
  }

  #[@test]
  public function in_method() {
    $r= $this->run('class <T> {
      public function withId(private $id) {
        return $this;
      }

      public function run() {
        return $this->withId("test")->id;
      }
    }');
    $this->assertEquals('test', $r);
  }
}