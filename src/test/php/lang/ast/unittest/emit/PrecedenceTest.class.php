<?php namespace lang\ast\unittest\emit;

use test\{Assert, Test, Values};

class PrecedenceTest extends EmittingTest {

  #[Test, Values([['2 + 3 * 4', 14], ['2 + 8 / 4', 4], ['2 + 3 ** 2', 11], ['2 + 5 % 2', 3]])]
  public function mathematical($input, $result) {
    Assert::equals($result, $this->run(
      'class %T {
        public function run() {
          return '.$input.';
        }
      }'
    ));
  }

  #[Test]
  public function concatenation() {
    $t= $this->declare(
      'class %T {
        public function run() {
          return "(".self::class.")";
        }
      }'
    );
    Assert::equals('('.$t->literal().')', $t->newInstance()->run());
  }

  #[Test]
  public function plusplus() {
    $t= $this->declare(
      'class %T {
        private $number= 1;

        public function run() {
          return ++$this->number;
        }
      }'
    );
    Assert::equals(2, $t->newInstance()->run());
  }
}