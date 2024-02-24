<?php namespace lang\ast\unittest\emit;

use lang\Error;
use test\{Assert, Expect, Test, Values};
use util\{Date, Bytes};

class TypeCheckingTest extends EmittingTest {

  #[Test, Values([1, true, false, '123', '0700', '-5'])]
  public function int_type_coerces($param) {
    $r= $this->run('class %T {
      public function run(int $param) {
        return $param;
      }
    }', $param);
    Assert::equals((int)$param, $r);
  }

  #[Test, Values(['', 'Test', 1, 1.5, true, false])]
  public function string_type_coerces($param) {
    $r= $this->run('class %T {
      public function run(string $param) {
        return $param;
      }
    }', $param);
    Assert::equals((string)$param, $r);
  }

  #[Test, Values([null, [[]]]), Expect(Error::class)]
  public function string_type_rejects($param) {
    $this->run('class %T {
      public function run(string $param) {
        return $param;
      }
    }', $param);
  }

  #[Test, Values(eval: '[[new Date(), new Bytes("test"), null]]')]
  public function nullable_value_type_accepts($param) {
    $r= $this->run('use lang\\Value; class %T {
      public function run(?Value $param) {
        return $param;
      }
    }', $param);
    Assert::equals($param, $r);
  }

  #[Test, Values(eval: '[["test", new Bytes("test")]]')]
  public function union_type_accepts($param) {
    $r= $this->run('class %T {
      public function run(string|Bytes $param) {
        return $param;
      }
    }', $param);
    Assert::equals($param, $r);
  }

  #[Test, Values(eval: '[[new Bytes("test")]]')]
  public function intersection_type_accepts($param) {
    $r= $this->run('use lang\\Value; class %T {
      public function run(Value&Traversable $param) {
        return $param;
      }
    }', $param);
    Assert::equals($param, $r);
  }

  #[Test, Values([[[]], [['a', 'b', 'c']]])]
  public function string_array_type_accepts($param) {
    $r= $this->run('class %T {
      public function run(array<string> $param) {
        return $param;
      }
    }', $param);
    Assert::equals((array)$param, $r);
  }
}