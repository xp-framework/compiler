<?php namespace lang\ast\unittest\parse;

class LoopsTest extends ParseTest {
  private $block;

  /** @return void */
  public function setUp() {
    $this->block= ['(' => [['loop' => 'loop'], []]];
  }

  #[@test]
  public function foreach_value() {
    $this->assertNodes(
      [['foreach' => [
        ['(variable)' => 'iterable'],
        null,
        ['(variable)' => 'value'],
        ['{' => [$this->block]]
      ]]],
      $this->parse('foreach ($iterable as $value) { loop(); }')
    );
  }

  #[@test]
  public function foreach_key_value() {
    $this->assertNodes(
      [['foreach' => [
        ['(variable)' => 'iterable'],
        ['(variable)' => 'key'],
        ['(variable)' => 'value'],
        ['{' => [$this->block]]
      ]]],
      $this->parse('foreach ($iterable as $key => $value) { loop(); }')
    );
  }

  #[@test]
  public function foreach_value_without_curly_braces() {
    $this->assertNodes(
      [['foreach' => [
        ['(variable)' => 'iterable'],
        null,
        ['(variable)' => 'value'],
        $this->block
      ]]],
      $this->parse('foreach ($iterable as $value) loop();')
    );
  }

  #[@test]
  public function for_loop() {
    $this->assertNodes(
      [['for' => [
        [['=' => [['(variable)' => 'i'], '=', ['(literal)' => '0']]]],
        [['<' => [['(variable)' => 'i'], '<', ['(literal)' => '10']]]],
        [['++' => [['(variable)' => 'i'], '++']]],
        ['{' => [$this->block]]
      ]]],
      $this->parse('for ($i= 0; $i < 10; $i++) { loop(); }')
    );
  }

  #[@test]
  public function while_loop() {
    $this->assertNodes(
      [['while' => [['(variable)' => 'continue'], ['{' => [$this->block]]]]],
      $this->parse('while ($continue) { loop(); }')
    );
  }

  #[@test]
  public function while_loop_without_curly_braces() {
    $this->assertNodes(
      [['while' => [['(variable)' => 'continue'], $this->block]]],
      $this->parse('while ($continue) loop();')
    );
  }

  #[@test]
  public function do_loop() {
    $this->assertNodes(
      [['do' => [['(variable)' => 'continue'], ['{' => [$this->block]]]]],
      $this->parse('do { loop(); } while ($continue);')
    );
  }

  #[@test]
  public function do_loop_without_curly_braces() {
    $this->assertNodes(
      [['do' => [['(variable)' => 'continue'], $this->block]]],
      $this->parse('do loop(); while ($continue);')
    );
  }

  #[@test]
  public function break_statement() {
    $this->assertNodes(
      [['break' => null]],
      $this->parse('break;')
    );
  }

  #[@test]
  public function break_statement_with_level() {
    $this->assertNodes(
      [['break' => ['(literal)' => '2']]],
      $this->parse('break 2;')
    );
  }

  #[@test]
  public function continue_statement() {
    $this->assertNodes(
      [['continue' => null]],
      $this->parse('continue;')
    );
  }

  #[@test]
  public function continue_statement_with_level() {
    $this->assertNodes(
      [['continue' => ['(literal)' => '2']]],
      $this->parse('continue 2;')
    );
  }

  #[@test, @ignore('Unsure how to implement')]
  public function goto_statement() {
    $this->assertNodes(
      [['do' => [['(variable)' => 'continue'], $this->block]]],
      $this->parse('label: loop(); goto loop;')
    );
  }
}