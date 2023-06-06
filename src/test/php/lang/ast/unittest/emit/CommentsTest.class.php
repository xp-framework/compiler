<?php namespace lang\ast\unittest\emit;

use test\{Assert, Test};

class CommentsTest extends EmittingTest {

  #[Test]
  public function on_class() {
    $t= $this->declare('/** Test */ class %T { }');
    Assert::equals('Test', $t->comment());
  }

  #[Test]
  public function on_interface() {
    $t= $this->declare('/** Test */ interface %T { }');
    Assert::equals('Test', $t->comment());
  }

  #[Test]
  public function on_trait() {
    $t= $this->declare('/** Test */ trait %T { }');
    Assert::equals('Test', $t->comment());
  }

  #[Test]
  public function on_enum() {
    $t= $this->declare('/** Test */ enum %T { }');
    Assert::equals('Test', $t->comment());
  }

  #[Test]
  public function on_method() {
    $t= $this->declare('class %T {

      /** Test */
      public function fixture() {
        // NOOP
      }
    }');

    Assert::equals('Test', $t->method('fixture')->comment());
  }

  #[Test]
  public function comments_are_escaped() {
    $t= $this->declare("/** Timm's test */ class %T { }");
    Assert::equals("Timm's test", $t->comment());
  }

  #[Test]
  public function only_last_comment_is_considered() {
    $t= $this->declare('class %T {

      /** Not the right comment */

      /** Test */
      public function fixture() {
        // NOOP
      }
    }');

    Assert::equals('Test', $t->method('fixture')->comment());
  }

  #[Test]
  public function next_comment_is_not_considered() {
    $t= $this->declare('class %T {

      /** Test */
      public function fixture() {
        // NOOP
      }

      /** Not the right comment */
    }');

    Assert::equals('Test', $t->method('fixture')->comment());
  }

  #[Test]
  public function inline_apidoc_comment_is_not_considered() {
    $t= $this->declare('class %T {

      /** Test */
      public function fixture() {
        /** Not the right comment */
      }
    }');

    Assert::equals('Test', $t->method('fixture')->comment());
  }
}