<?php namespace lang\ast\unittest\emit;

use unittest\{Assert, Test};

class PHP81Test extends EmittingTest {

  /** @return string */
  protected function runtime() { return 'PHP.8.1.0'; }

  #[Test]
  public function named_argument() {
    Assert::equals('f(key:"value");', $this->emit('f(key: "value");'));
  }

  #[Test]
  public function named_arguments() {
    Assert::equals('f(color:"green",price:12.50);', $this->emit('f(color: "green", price: 12.50);'));
  }

  #[Test]
  public function new_type() {
    Assert::equals('new \\T();', $this->emit('new T();'));
  }

  #[Test]
  public function new_expression() {
    Assert::equals('new ($this->class)();', $this->emit('new ($this->class)();'));
  }

  #[Test]
  public function throw_expression() {
    Assert::equals('fn()=>throw new \\T();', $this->emit('fn() => throw new T();'));
  }

  #[Test]
  public function catch_without_variable() {
    Assert::equals('try {}catch(\\T) {};', $this->emit('try { } catch (\\T) { }'));
  }

  #[Test]
  public function catch_without_types() {
    Assert::equals('try {}catch(\\Throwable $e) {};', $this->emit('try { } catch ($e) { }'));
  }

  #[Test]
  public function multi_catch_without_variable() {
    Assert::equals('try {}catch(\\A|\\B) {};', $this->emit('try { } catch (\\A | \\B) { }'));
  }

  #[Test]
  public function null_safe() {
    Assert::equals('$person?->name;', $this->emit('$person?->name;'));
  }

  #[Test]
  public function null_safe_expression() {
    Assert::equals('$person?->{$name};', $this->emit('$person?->{$name};'));
  }

  #[Test]
  public function match() {
    Assert::equals('match (true) {};', $this->emit('match (true) { };'));
  }

  #[Test]
  public function match_without_expression() {
    Assert::equals('match (true) {};', $this->emit('match { };'));
  }

  #[Test]
  public function match_with_case() {
    Assert::equals('match ($v) {1=>true,};', $this->emit('match ($v) { 1 => true };'));
  }

  #[Test]
  public function match_with_case_and_default() {
    Assert::equals('match ($v) {1=>true,default=>false};', $this->emit('match ($v) { 1 => true, default => false };'));
  }
}