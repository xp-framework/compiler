<?php namespace lang\ast\unittest\emit;

use lang\ClassCastException;

class CastingTest extends EmittingTest {

  #[@test, @values([
  #  0, 1, -1, 0.5, -1.5,
  #  "", "test",
  #  true, false
  #])]
  public function string_cast($value) {
    $this->assertEquals((string)$value, $this->run(
      'class <T> {
        public function run($value) {
          return (string)$value;
        }
      }',
      $value
    ));
  }

  #[@test, @values([
  #  "0", "1", "-1", "6100",
  #  "",
  #  0.5, -1.5,
  #  0, 1, -1,
  #  true, false
  #])]
  public function int_cast($value) {
    $this->assertEquals((int)$value, $this->run(
      'class <T> {
        public function run($value) {
          return (int)$value;
        }
      }',
      $value
    ));
  }

  #[@test, @values([
  #  [[]],
  #  [[0, 1, 2]],
  #  [['key' => 'value']],
  #  null, false, true, 1, 1.5, "", "test"
  #])]
  public function array_cast($value) {
    $this->assertEquals((array)$value, $this->run(
      'class <T> {
        public function run($value) {
          return (array)$value;
        }
      }',
      $value
    ));
  }

  #[@test]
  public function value_cast() {
    $this->assertEquals($this, $this->run(
      'class <T> {
        public function run($value) {
          return (\lang\ast\unittest\emit\CastingTest)$value;
        }
      }',
      $this
    ));
  }

  #[@test]
  public function int_array_cast() {
    $this->assertEquals([1, 2, 3], $this->run(
      'class <T> {
        public function run($value) {
          return (array<int>)$value;
        }
      }',
      [1, 2, 3]
    ));
  }

  #[@test, @expect(ClassCastException::class)]
  public function cannot_cast_object_to_int_array() {
    $this->run('class <T> {
      public function run() {
        return (array<int>)$this;
      }
    }');
  }

  #[@test]
  public function nullable_string_cast() {
    $this->assertNull($this->run(
      'class <T> {
        public function run($value) {
          return (?string)$value;
        }
      }',
      null
    ));
  }
}