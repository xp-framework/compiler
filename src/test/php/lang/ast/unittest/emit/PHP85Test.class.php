<?php namespace lang\ast\unittest\emit;

use lang\ast\emit\Type;
use test\{Assert, Test};

class PHP85Test extends EmittingTest {

  /** @return string */
  protected function runtime() { return 'php:8.5.0'; }

  #[Test]
  public function pipe_operator() {
    Assert::equals(
      '"test"|>strtoupper(...);',
      $this->emit('"test" |> strtoupper(...);')
    );
  }

  #[Test]
  public function nullsafepipe_operator() {
    Assert::equals(
      'null===($_0="test")?null:$_0|>strtoupper(...);',
      $this->emit('"test" ?|> strtoupper(...);')
    );
  }

  #[Test]
  public function clone_with() {
    Assert::equals(
      'clone($this,["name"=>strtoupper($this->name),]);',
      $this->emit('clone($this, ["name" => strtoupper($this->name)]);')
    );
  }
}