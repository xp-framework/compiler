<?php namespace lang\ast\unittest\emit;

use lang\IllegalStateException;
use lang\ast\Errors;
use test\{Assert, Expect, Test};

class SyntaxErrorsTest extends EmittingTest {

  #[Test, Expect(class: Errors::class, message: '/Missing semicolon after assignment statement/')]
  public function missing_semicolon() {
    $this->emit('$greeting= hello() world()');
  }

  #[Test, Expect(class: Errors::class, message: '/Unexpected :/')]
  public function unexpected_colon() {
    $this->emit('$greeting= hello();:');
  }

  #[Test, Expect(class: IllegalStateException::class, message: '/Unexpected operator =/')]
  public function operator() {
    $this->emit('=;');
  }
}