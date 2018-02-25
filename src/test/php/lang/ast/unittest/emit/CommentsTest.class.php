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

  #[@test]
  public function only_last_comment_is_considered() {
    $t= $this->type('class <T> {

      /** Not the right comment */

      /** Test */
      public function fixture() {
        // NOOP
      }
    }');

    $this->assertEquals('Test', $t->getMethod('fixture')->getComment());
  }

  #[@test]
  public function next_comment_is_not_considered() {
    $t= $this->type('class <T> {

      /** Test */
      public function fixture() {
        // NOOP
      }

      /** Not the right comment */
    }');

    $this->assertEquals('Test', $t->getMethod('fixture')->getComment());
  }

  #[@test]
  public function inline_apidoc_comment_is_not_considered() {
    $t= $this->type('class <T> {

      /** Test */
      public function fixture() {
        /** Not the right comment */
      }
    }');

    $this->assertEquals('Test', $t->getMethod('fixture')->getComment());
  }
}