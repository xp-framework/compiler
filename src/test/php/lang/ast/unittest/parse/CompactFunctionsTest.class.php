<?php namespace lang\ast\unittest\parse;

class CompactFunctionsTest extends ParseTest {

  #[@test]
  public function function_returning_null() {
    $this->assertNodes(
      [['function' => ['a', [[], null], [['==>' => ['null' => 'null']]]]]],
      $this->parse('function a() ==> null;')
    );
  }

  #[@test]
  public function short_method() {
    $block= [['==>' => ['true' => 'true']]];
    $this->assertNodes(
      [['class' => ['\\A', [], null, [], [
        ['function' => ['a', ['public'], [[], null], null, $block]]
      ], []]]],
      $this->parse('class A { public function a() ==> true; }')
    );
  }
}