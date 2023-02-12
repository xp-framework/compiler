<?php namespace lang\ast\unittest\emit;

use lang\ClassCastException;
use test\{Assert, Expect, Test, Values};

class CastingTest extends EmittingTest {

  /** @return string */
  public function test() { return 'Test'; }

  #[Test, Values([0, 1, -1, 0.5, -1.5, '', 'test', true, false])]
  public function string_cast($value) {
    Assert::equals((string)$value, $this->run(
      'class <T> {
        public function run($value) {
          return (string)$value;
        }
      }',
      $value
    ));
  }

  #[Test, Values(['0', '1', '-1', '6100', '', 0.5, -1.5, 0, 1, -1, true, false])]
  public function int_cast($value) {
    Assert::equals((int)$value, $this->run(
      'class <T> {
        public function run($value) {
          return (int)$value;
        }
      }',
      $value
    ));
  }

  #[Test, Values([[[]], [[0, 1, 2]], [['key' => 'value']], null, false, true, 1, 1.5, '', 'test'])]
  public function array_cast($value) {
    Assert::equals((array)$value, $this->run(
      'class <T> {
        public function run($value) {
          return (array)$value;
        }
      }',
      $value
    ));
  }

  #[Test]
  public function value_cast() {
    Assert::equals($this, $this->run(
      'class <T> {
        public function run($value) {
          return (\lang\ast\unittest\emit\CastingTest)$value;
        }
      }',
      $this
    ));
  }

  #[Test]
  public function int_array_cast() {
    Assert::equals([1, 2, 3], $this->run(
      'class <T> {
        public function run($value) {
          return (array<int>)$value;
        }
      }',
      [1, 2, 3]
    ));
  }

  #[Test, Expect(ClassCastException::class)]
  public function cannot_cast_object_to_int_array() {
    $this->run('class <T> {
      public function run() {
        return (array<int>)$this;
      }
    }');
  }

  #[Test, Values([null, 'test'])]
  public function nullable_string_cast_of($value) {
    Assert::equals($value, $this->run(
      'class <T> {
        public function run($value) {
          return (?string)$value;
        }
      }',
      $value
    ));
  }

  #[Test, Values([null, 'test'])]
  public function nullable_string_cast_of_expression_returning($value) {
    Assert::equals($value, $this->run(
      'class <T> {
        public function run($value) {
          $values= [$value];
          return (?string)array_pop($values);
        }
      }',
      $value
    ));
  }

  #[Test]
  public function cast_braced() {
    Assert::equals(['test'], $this->run(
      'class <T> {
        public function run($value) {
          return (array<string>)($value);
        }
      }',
      ['test']
    ));
  }

  #[Test]
  public function cast_to_function_type_and_invoke() {
    Assert::equals($this->test(), $this->run(
      'class <T> {
        public function run($value) {
          return ((function(): string)($value))();
        }
      }',
      [$this, 'test']
    ));
  }

  #[Test]
  public function object_cast_on_literal() {
    Assert::equals((object)['key' => 'value'], $this->run(
      'class <T> {
        public function run($value) {
          return (object)["key" => "value"];
        }
      }',
      ['test']
    ));
  }

  #[Test, Values([[1, 1], ['123', 123], [null, null]])]
  public function nullable_int($value, $expected) {
    Assert::equals($expected, $this->run(
      'class <T> {
        public function run($value) {
          return (?int)$value;
        }
      }',
      $value
    ));
  }

  #[Test, Values(eval: '[new Handle(10), null]')]
  public function nullable_value($value) {
    Assert::equals($value, $this->run(
      'class <T> {
        public function run($value) {
          return (?\lang\ast\unittest\emit\Handle)$value;
        }
      }',
      $value
    ));
  }
}