<?php namespace lang\ast\unittest\emit;

use test\{Assert, Test, Values};

class ScalarsTest extends EmittingTest {

  #[Test, Values([['0', 0], ['1', 1], ['-1', -1], ['1.5', 1.5], ['-1.5', -1.5],])]
  public function numbers($literal, $result) {
    Assert::equals($result, $this->run('class %T { public function run() { return '.$literal.'; } }'));
  }

  #[Test, Values([['0b0', 0], ['0b10', 2], ['0B10', 2]])]
  public function binary_numbers($literal, $result) {
    Assert::equals($result, $this->run('class %T { public function run() { return '.$literal.'; } }'));
  }

  #[Test, Values([['0x0', 0], ['0xff', 255], ['0xFF', 255], ['0XFF', 255]])]
  public function hexadecimal_numbers($literal, $result) {
    Assert::equals($result, $this->run('class %T { public function run() { return '.$literal.'; } }'));
  }

  #[Test, Values([['0755', 493], ['0o16', 14], ['0O16', 14]])]
  public function octal_numbers($literal, $result) {
    Assert::equals($result, $this->run('class %T { public function run() { return '.$literal.'; } }'));
  }

  #[Test, Values([['135_99', 13599], ['107_925_284.88', 107925284.88], ['0xCAFE_F00D', 3405705229], ['0b0101_1111', 95], ['0137_041', 48673],])]
  public function numeric_literal_separator($literal, $result) {
    Assert::equals($result, $this->run('class %T { public function run() { return '.$literal.'; } }'));
  }

  #[Test, Values([['""', ''], ['"Test"', 'Test'],])]
  public function strings($literal, $result) {
    Assert::equals($result, $this->run('class %T { public function run() { return '.$literal.'; } }'));
  }

  #[Test, Values([['true', true], ['false', false], ['null', null],])]
  public function constants($literal, $result) {
    Assert::equals($result, $this->run('class %T { public function run() { return '.$literal.'; } }'));
  }
}