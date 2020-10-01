<?php namespace lang\ast\unittest\emit;

use unittest\{Assert, Test};

class CommentsTest extends EmittingTest {

  #[Test]
  public function on_class() {
    $t= $this->type('/** Test */ class <T> { }');
    Assert::equals('Test', $t->getComment());
  }

  #[Test]
  public function on_interface() {
    $t= $this->type('/** Test */ interface <T> { }');
    Assert::equals('Test', $t->getComment());
  }

  #[Test]
  public function on_trait() {
    $t= $this->type('/** Test */ trait <T> { }');
    Assert::equals('Test', $t->getComment());
  }

  #[Test]
  public function on_method() {
    $t= $this->type('class <T> {

      /** Test */
      public function fixture() {
        // NOOP
      }
    }');

    Assert::equals('Test', $t->getMethod('fixture')->getComment());
  }

  #[Test]
  public function comments_are_escaped() {
    $t= $this->type("/** Timm's test */ class <T> { }");
    Assert::equals("Timm's test", $t->getComment());
  }

  #[Test]
  public function only_last_comment_is_considered() {
    $t= $this->type('class <T> {

      /** Not the right comment */

      /** Test */
      public function fixture() {
        // NOOP
      }
    }');

    Assert::equals('Test', $t->getMethod('fixture')->getComment());
  }

  #[Test]
  public function next_comment_is_not_considered() {
    $t= $this->type('class <T> {

      /** Test */
      public function fixture() {
        // NOOP
      }

      /** Not the right comment */
    }');

    Assert::equals('Test', $t->getMethod('fixture')->getComment());
  }

  #[Test]
  public function inline_apidoc_comment_is_not_considered() {
    $t= $this->type('class <T> {

      /** Test */
      public function fixture() {
        /** Not the right comment */
      }
    }');

    Assert::equals('Test', $t->getMethod('fixture')->getComment());
  }
}