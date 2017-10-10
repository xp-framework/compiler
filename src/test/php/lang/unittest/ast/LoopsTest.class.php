<?php namespace lang\unittest\ast;

class LoopsTest extends ParseTest {
  private $block;

  /** @return void */
  public function setUp() {
    $this->block= [['(' => [['loop' => 'loop'], []]]];
  }

  #[@test]
  public function foreach_value() {
    $this->assertNodes(
      [['foreach' => [['(variable)' => 'iterable'], null, ['(variable)' => 'value'], $this->block]]],
      $this->parse('foreach ($iterable as $value) { loop(); }')
    );
  }

  #[@test]
  public function foreach_key_value() {
    $this->assertNodes(
      [['foreach' => [['(variable)' => 'iterable'], ['(variable)' => 'key'], ['(variable)' => 'value'], $this->block]]],
      $this->parse('foreach ($iterable as $key => $value) { loop(); }')
    );
  }

  #[@test]
  public function while_loop() {
    $this->assertNodes(
      [['while' => [['(variable)' => 'continue'], $this->block]]],
      $this->parse('while ($continue) { loop(); }')
    );
  }

  #[@test]
  public function do_loop() {
    $this->assertNodes(
      [['do' => [['(variable)' => 'continue'], $this->block]]],
      $this->parse('do { loop(); } while ($continue)')
    );
  }
}