<?php namespace lang\ast\unittest\emit;

use lang\IllegalStateException;
use lang\ast\Errors;
use unittest\{Assert, Expect, Test};

class SyntaxErrorsTest extends EmittingTest {

  #[Test, Expect(class: Errors::class, withMessage: 'Missing semicolon after assignment statement')]
  public function missing_semicolon() {
    $this->emit('$greeting= hello() world()');
  }

  #[Test, Expect(class: Errors::class, withMessage: 'Unexpected :')]
  public function unexpected_colon() {
    $this->emit('$greeting= hello();:');
  }

  #[Test, Expect(class: IllegalStateException::class, withMessage: 'Unexpected operator = at line 1')]
  public function operator() {
    $this->emit('=;');
  }
}
