<?php namespace lang\ast\unittest\parse;

use lang\ast\Error;

class ErrorsTest extends ParseTest {

  /**
   * Assertion helper
   *
   * @param  string $message
   * @param  iterable $parse
   * @throws unittest.AssertionFailedError
   */
  private function assertError($message, $parse) {
    try {
      iterator_to_array($parse);
      $this->fail('No exception raised', null, Error::class);
    } catch (Error $expected) {
      $this->assertEquals($message, $expected->getMessage());
    }
  }

  #[@test]
  public function missing_semicolon() {
    $this->assertError('Expected `;`, have `(variable)`', $this->parse('$a= 1 $b= 1;'));
  }

  #[@test]
  public function illegal_token() {
    $this->assertError('Expected `) or ,`, have `(end)`', $this->parse('call('));
  }
}