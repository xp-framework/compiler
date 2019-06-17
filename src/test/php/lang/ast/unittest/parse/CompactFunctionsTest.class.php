<?php namespace lang\ast\unittest\parse;

class CompactFunctionsTest extends ParseTest {

  #[@test]
  public function function_returning_null() {
    $this->assertNodes(
      [['function' => ['a', [[], null], [['==>' => ['null' => 'null']]]]]],
      $this->parse('function a() ==> null;')
    );
    \xp::gc();
  }

  #[@test]
  public function short_method() {
    $block= [['==>' => ['true' => 'true']]];
    $this->assertNodes(
      [['class' => ['\\A', [], null, [], [
        'a()' => ['function' => ['a', ['public'], [[], null], [], $block, null]]
      ], [], null]]],
      $this->parse('class A { public function a() ==> true; }')
    );
    \xp::gc();
  }
}