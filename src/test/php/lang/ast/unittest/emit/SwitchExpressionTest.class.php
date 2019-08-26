<?php namespace lang\ast\unittest\emit;

use lang\IllegalArgumentException;
use lang\Runnable;

class SwitchExpressionTest extends EmittingTest {
  private $fixture;

  /** @return void */
  public function setUp() {
    $this->fixture= $this->type('use lang\{Runnable, IllegalArgumentException}; class <T> {
      public function run($arg) {
        return switch ($arg) {
          case true, false => "bool";
          case null        => "void";
          case (int)       => "integer";
          case (string)    => "string";
          case (Runnable)  => "runnable";
          default          => throw new IllegalArgumentException("Unhandled ".typeof($arg));
        };
      }
    }');
  }

  #[@test, @values([
  #  [true, 'bool'],
  #  [false, 'bool'],
  #  [null, 'void'],
  #])]
  public function exact_comparison($arg, $expected) {
    $this->assertEquals($expected, $this->fixture->newInstance()->run($arg));
  }

  #[@test, @values([
  #  [1, 'integer'],
  #  ['Test', 'string'],
  #])]
  public function native_type_comparison($arg, $expected) {
    $this->assertEquals($expected, $this->fixture->newInstance()->run($arg));
  }

  #[@test]
  public function value_type_comparison() {
    $this->assertEquals('runnable', $this->fixture->newInstance()->run(newinstance(Runnable::class, [], [
      'run' => function() { }
    ])));
  }

  #[@test, @expect(IllegalArgumentException::class)]
  public function unhandled() {
    $this->fixture->newInstance()->run($this);
  }
}