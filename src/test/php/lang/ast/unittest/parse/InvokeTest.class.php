<?php namespace lang\ast\unittest\parse;

/**
 * Invocation expressions
 *
 * @see  https://wiki.php.net/rfc/trailing-comma-function-calls
 */
class InvokeTest extends ParseTest {

  #[@test]
  public function invoke_function() {
    $this->assertNodes(
      [['(' => [['test' => 'test'], []]]],
      $this->parse('test();')
    );
  }

  #[@test]
  public function invoke_method() {
    $this->assertNodes(
      [['(' => [['->' => [['(variable)' => 'this'], ['test' => 'test']]], []]]],
      $this->parse('$this->test();')
    );
  }

  #[@test]
  public function invoke_function_with_argument() {
    $this->assertNodes(
      [['(' => [['test' => 'test'], [['(literal)' => '1']]]]],
      $this->parse('test(1);')
    );
  }

  #[@test]
  public function invoke_function_with_arguments() {
    $this->assertNodes(
      [['(' => [['test' => 'test'], [['(literal)' => '1'], ['(literal)' => '2']]]]],
      $this->parse('test(1, 2);')
    );
  }

  #[@test]
  public function invoke_function_with_dangling_comma() {
    $this->assertNodes(
      [['(' => [['test' => 'test'], [['(literal)' => '1'], ['(literal)' => '2']]]]],
      $this->parse('test(1, 2, );')
    );
  }
}