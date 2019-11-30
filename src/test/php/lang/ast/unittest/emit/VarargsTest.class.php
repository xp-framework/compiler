<?php namespace lang\ast\unittest\emit;

use unittest\Assert;

class VarargsTest extends EmittingTest {

  #[@test]
  public function vsprintf() {
    $r= $this->run('class <T> {
      private fn format(string $format, ... $args) => vsprintf($format, $args);

      public function run() {
        return $this->format("Hello %s", "Test");
      }
    }');

    Assert::equals('Hello Test', $r);
  }

  #[@test]
  public function list_of() {
    $r= $this->run('class <T> {
      private fn listOf(string... $args) => $args;

      public function run() {
        return $this->listOf("Hello", "Test");
      }
    }');

    Assert::equals(['Hello', 'Test'], $r);
  }
}