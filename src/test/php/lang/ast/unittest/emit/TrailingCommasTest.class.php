<?php namespace lang\ast\unittest\emit;

use unittest\Assert;

class TrailingCommasTest extends EmittingTest {

  #[@test]
  public function in_array() {
    $r= $this->run('class <T> { public function run() { return ["test", ]; } }');
    Assert::equals(['test'], $r);
  }

  #[@test]
  public function in_map() {
    $r= $this->run('class <T> { public function run() { return ["test" => true, ]; } }');
    Assert::equals(['test' => true], $r);
  }

  #[@test]
  public function in_function_call() {
    $r= $this->run('class <T> { public function run() { return sprintf("Hello %s", "test", ); } }');
    Assert::equals('Hello test', $r);
  }

  #[@test]
  public function in_parameter_list() {
    $r= $this->run('class <T> { public function run($a, ) { return $a; } }', 'Test');
    Assert::equals('Test', $r);
  }

  #[@test]
  public function in_isset() {
    $r= $this->run('class <T> { public function run() { return isset($a, ); } }');
    Assert::equals(false, $r);
  }

  #[@test]
  public function in_list() {
    $r= $this->run('class <T> { public function run() { list($a, )= [1, 2]; return $a; } }');
    Assert::equals(1, $r);
  }

  #[@test]
  public function in_short_list() {
    $r= $this->run('class <T> { public function run() { [$a, ]= [1, 2]; return $a; } }');
    Assert::equals(1, $r);
  }

  #[@test]
  public function in_namespace_group() {
    $r= $this->run('use lang\\{Type, }; class <T> { public function run() { return Type::$ARRAY->getName(); } }');
    Assert::equals('array', $r);
  }
}