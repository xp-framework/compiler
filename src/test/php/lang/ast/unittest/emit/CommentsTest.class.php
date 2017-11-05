<?php namespace lang\ast\unittest\emit;

class CommentsTest extends EmittingTest {

  #[@test]
  public function on_class() {
    $t= $this->type('/** Test */ class <T> { }');
    $this->assertEquals('Test', $t->getComment());
  }

  #[@test]
  public function on_interface() {
    $t= $this->type('/** Test */ interface <T> { }');
    $this->assertEquals('Test', $t->getComment());
  }

  #[@test]
  public function on_trait() {
    $t= $this->type('/** Test */ trait <T> { }');
    $this->assertEquals('Test', $t->getComment());
  }

  #[@test]
  public function on_method() {
    $t= $this->type('class <T> {

      /** Test */
      public function fixture() {
        // NOOP
      }
    }');

    $this->assertEquals('Test', $t->getMethod('fixture')->getComment());
  }

  #[@test]
  public function comments_are_escaped() {
    $t= $this->type("/** Timm's test */ class <T> { }");
    $this->assertEquals("Timm's test", $t->getComment());
  }

}