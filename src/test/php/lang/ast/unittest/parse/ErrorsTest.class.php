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
    $this->assertError(
      'Expected ";", have "(variable)"',
      $this->parse('$a= 1 $b= 1;')
    );
  }

  #[@test]
  public function unclosed_brace_in_arguments() {
    $this->assertError(
      'Expected ") or ,", have "(end)" in argument list',
      $this->parse('call(')
    );
  }

  #[@test]
  public function unclosed_brace_in_parameters() {
    $this->assertError(
      'Expected ",", have "(end)" in parameter list',
      $this->parse('function($a')
    );
  }

  #[@test]
  public function unclosed_type() {
    $this->assertError(
      'Expected "a type, modifier, property, annotation, method or }", have "-" in type body',
      $this->parse('class T { -')
    );
  }

  #[@test]
  public function missing_comma_in_implements() {
    $this->assertError(
      'Expected ", or {", have "(name)" in interfaces list',
      $this->parse('class A implements I B')
    );
  }

  #[@test]
  public function missing_comma_in_interface_parents() {
    $this->assertError(
      'Expected ", or {", have "(name)" in interface parents',
      $this->parse('interface I extends A B')
    );
  }

  #[@test]
  public function unclosed_annotation() {
    $this->assertError(
      'Expected ", or >>", have "(end)" in annotation',
      $this->parse('<<annotation')
    );
  }
}