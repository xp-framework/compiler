<?php namespace lang\ast\unittest\emit;

class TrailingCommasTest extends EmittingTest {

  #[@test]
  public function in_array() {
    $r= $this->run('class <T> { public function run() { return ["test", ]; } }');
    $this->assertEquals(['test'], $r);
  }

  #[@test]
  public function in_map() {
    $r= $this->run('class <T> { public function run() { return ["test" => true, ]; } }');
    $this->assertEquals(['test' => true], $r);
  }

  #[@test]
  public function in_function_call() {
    $r= $this->run('class <T> { public function run() { return sprintf("Hello %s", "test", ); } }');
    $this->assertEquals('Hello test', $r);
  }

  #[@test]
  public function in_parameter_list() {
    $r= $this->run('class <T> { public function run($a, ) { return $a; } }', 'Test');
    $this->assertEquals('Test', $r);
  }

  #[@test]
  public function in_isset() {
    $r= $this->run('class <T> { public function run() { return isset($a, ); } }');
    $this->assertEquals(false, $r);
  }

  #[@test]
  public function in_list() {
    $r= $this->run('class <T> { public function run() { list($a, )= [1, 2]; return $a; } }');
    $this->assertEquals(1, $r);
  }

  #[@test]
  public function in_short_list() {
    $r= $this->run('class <T> { public function run() { [$a, ]= [1, 2]; return $a; } }');
    $this->assertEquals(1, $r);
  }

  #[@test]
  public function in_namespace_group() {
    $r= $this->run('use lang\\{Type, }; class <T> { public function run() { return Type::$ARRAY->getName(); } }');
    $this->assertEquals('array', $r);
  }
}