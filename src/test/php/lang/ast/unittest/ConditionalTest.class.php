<?php namespace lang\ast\unittest;

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

  #[@test]
  public function empty_switch() {
    $this->assertNodes(
      [['switch' => [['(variable)' => 'condition'], []]]],
      $this->parse('switch ($condition) { }')
    );
  }

  #[@test]
  public function switch_with_one_case() {
    $this->assertNodes(
      [['switch' => [['(variable)' => 'condition'], [
        [['(literal)' => 1], $this->blocks[0]],
      ]]]],
      $this->parse('switch ($condition) { case 1: action1(); }')
    );
  }

  #[@test]
  public function switch_with_two_cases() {
    $this->assertNodes(
      [['switch' => [['(variable)' => 'condition'], [
        [['(literal)' => 1], $this->blocks[0]],
        [['(literal)' => 2], $this->blocks[1]],
      ]]]],
      $this->parse('switch ($condition) { case 1: action1(); case 2: action2(); }')
    );
  }

  #[@test]
  public function switch_with_default() {
    $this->assertNodes(
      [['switch' => [['(variable)' => 'condition'], [
        [null, $this->blocks[0]]
      ]]]],
      $this->parse('switch ($condition) { default: action1(); }')
    );
  }
}