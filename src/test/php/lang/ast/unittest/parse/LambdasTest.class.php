<?php namespace lang\ast\unittest\parse;

class LambdasTest extends ParseTest {

  #[@test]
  public function short_closure() {
    $block= ['+' => [['(variable)' => 'a'], '+', ['(literal)' => '1']]];
    $this->assertNodes(
      [['fn' => [[[['a', false, null, false, null, null, []]], null], $block]]],
      $this->parse('fn($a) => $a + 1;')
    );
  }

  #[@test]
  public function short_closure_as_arg() {
    $block= ['+' => [['(variable)' => 'a'], '+', ['(literal)' => '1']]];
    $this->assertNodes(
      [['(' => [['(name)' => 'exec'], [
        ['fn' => [[[['a', false, null, false, null, null, []]], null], $block]]
      ]]]],
      $this->parse('exec(fn($a) => $a + 1);')
    );
  }

  #[@test]
  public function short_closure_with_braces() {
    $block= ['+' => [['(variable)' => 'a'], '+', ['(literal)' => '1']]];
    $this->assertNodes(
      [['fn' => [[[['a', false, null, false, null, null, []]], null], [['return' => $block]]]]],
      $this->parse('fn($a) => { return $a + 1; };')
    );
  }
}