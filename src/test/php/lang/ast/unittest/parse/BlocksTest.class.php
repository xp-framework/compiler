<?php namespace lang\ast\unittest\parse;

class BlocksTest extends ParseTest {
  private $block;

  /** @return void */
  public function setUp() {
    $this->block= [['(' => [['block' => 'block'], []]]];
  }

  #[@test]
  public function static_variable() {
    $this->assertNodes(
      [['{' => $this->block]],
      $this->parse('{ block(); }')
    );
  }
}