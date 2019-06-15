<?php namespace lang\ast\unittest\parse;

class CompactFunctionsTest extends ParseTest {

  #[@test]
  public function function_returning_null() {
    $this->assertNodes(
      [['fn' => [[[], null], ['null' => 'null']]]],
      $this->parse('fn() => null;')
    );
  }

  #[@test]
  public function short_method() {
    $block= [['=>' => ['true' => 'true']]];
    $this->assertNodes(
      [['class' => ['\\A', [], null, [], [
        'a()' => ['fn' => ['a', ['public'], [[], null], [], $block, null]]
      ], [], null]]],
      $this->parse('class A { public fn a() => true; }')
    );
  }
}