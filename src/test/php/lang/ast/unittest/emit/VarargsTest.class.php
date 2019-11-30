<?php namespace lang\ast\unittest\emit;

use unittest\Assert;

class VarargsTest extends EmittingTest {

  #[@test]
  public function vsprintf() {
    $r= $this->run('class <T> {
      private function format(string $format, ... $args) {
        return vsprintf($format, $args);
      }

      public function run() {
        return $this->format("Hello %s", "Test");
      }
    }');

    Assert::equals('Hello Test', $r);
  }

  #[@test]
  public function list_of() {
    $r= $this->run('class <T> {
      private function listOf(string... $args) {
        return $args;
      }

      public function run() {
        return $this->listOf("Hello", "Test");
      }
    }');

    Assert::equals(['Hello', 'Test'], $r);
  }
}