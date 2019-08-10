<?php namespace lang\ast\unittest\emit;

/**
 * Compact functions syntax
 *
 * @see  https://github.com/xp-framework/rfc/issues/241
 * @see  https://wiki.php.net/rfc/short_closures#other_uses_for_operator (Declined)
 */
class CompactFunctionsTest extends EmittingTest {

  #[@test]
  public function with_scalar() {
    $r= $this->run('class <T> {
      public fn run() => "test";
    }');
    $this->assertEquals('test', $r);
  }

  #[@test]
  public function with_property() {
    $r= $this->run('class <T> {
      private $id= "test";

      public fn run() => $this->id;
    }');
    $this->assertEquals('test', $r);
  }

  #[@test]
  public function combined_with_argument_promotion() {
    $r= $this->run('class <T> {
      public fn withId(private $id) => $this;
      public fn id() => $this->id;

      public function run() {
        return $this->withId("test")->id();
      }
    }');
    $this->assertEquals('test', $r);
  }

  #[@test]
  public function hacklang_variation_also_supported() {
    $r= $this->run('class <T> {
      public function run() ==> "test";
    }');
    $this->assertEquals('test', $r);
    \xp::gc();
  }
}