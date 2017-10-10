<?php namespace lang\unittest\ast;

class ConditionalTest extends ParseTest {
  private $blocks;

  /** @return void */
  public function setUp() {
    $this->blocks= [
      [['(' => [['action1' => 'action1'], []]]],
      [['(' => [['action2' => 'action2'], []]]]
    ];
  }

  #[@test]
  public function plain_if() {
    $this->assertNodes(
      [['if' => [['(variable)' => 'condition'], $this->blocks[0], null]]],
      $this->parse('if ($condition) { action1(); }')
    );
  }

  #[@test]
  public function if_with_else() {
    $this->assertNodes(
      [['if' => [['(variable)' => 'condition'], $this->blocks[0], $this->blocks[1]]]],
      $this->parse('if ($condition) { action1(); } else { action2(); }')
    );
  }

  #[@test]
  public function shortcut_if() {
    $this->assertNodes(
      [['if' => [['(variable)' => 'condition'], $this->blocks[0], null]]],
      $this->parse('if ($condition) action1();')
    );
  }

  #[@test]
  public function shortcut_if_else() {
    $this->assertNodes(
      [['if' => [['(variable)' => 'condition'], $this->blocks[0], $this->blocks[1]]]],
      $this->parse('if ($condition) action1(); else action2();')
    );
  }
}