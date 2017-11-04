<?php namespace lang\ast\unittest\parse;

class StartTokensTest extends ParseTest {

  #[@test]
  public function php() {
    $this->assertNodes(
      [['<?' => 'php'], ['namespace' => 'test']],
      $this->parse('<?php namespace test;')
    );
  }

  #[@test]
  public function hack() {
    $this->assertNodes(
      [['<?' => 'hh'], ['namespace' => 'test']],
      $this->parse('<?hh namespace test;')
    );
  }
}