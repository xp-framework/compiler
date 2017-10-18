<?php namespace lang\ast\unittest\emit;

use lang\IllegalArgumentException;

class ExceptionsTest extends EmittingTest {

  #[@test]
  public function catch_exception() {
    $t= $this->type('class <T> {
      public function run() {
        try {
          throw new \\lang\\IllegalArgumentException("test");
        } catch (\\lang\\IllegalArgumentException $expected) {
          return get_class($expected);
        }
      }
    }');

    $this->assertEquals(IllegalArgumentException::class, $t->newInstance()->run());
  }

  #[@test]
  public function line_number_matches() {
    $t= $this->type('class <T> {
      public function run() {
        try {
          throw new \\lang\\IllegalArgumentException("test");
        } catch (\\lang\\IllegalArgumentException $expected) {
          return $expected->getLine();
        }
      }
    }');

    $this->assertEquals(4, $t->newInstance()->run());
  }
}