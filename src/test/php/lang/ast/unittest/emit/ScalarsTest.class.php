<?php namespace lang\ast\unittest\emit;

use unittest\{Assert, Test, Values};

class ScalarsTest extends EmittingTest {

  #[Test, Values([['0', 0], ['1', 1], ['-1', -1], ['0xff', 255], ['0755', 493], ['1.5', 1.5], ['-1.5', -1.5],])]
  public function numbers($literal, $result) {
    Assert::equals($result, $this->run('class <T> { public function run() { return '.$literal.'; } }'));
  }

  #[Test, Values([['135_99', 13599], ['107_925_284.88', 107925284.88], ['0xCAFE_F00D', 3405705229], ['0b0101_1111', 95], ['0137_041', 48673],])]
  public function numeric_literal_separator($literal, $result) {
    Assert::equals($result, $this->run('class <T> { public function run() { return '.$literal.'; } }'));
  }

  #[Test, Values([['""', ''], ['"Test"', 'Test'],])]
  public function strings($literal, $result) {
    Assert::equals($result, $this->run('class <T> { public function run() { return '.$literal.'; } }'));
  }

  #[Test, Values([['true', true], ['false', false], ['null', null],])]
  public function constants($literal, $result) {
    Assert::equals($result, $this->run('class <T> { public function run() { return '.$literal.'; } }'));
  }
}