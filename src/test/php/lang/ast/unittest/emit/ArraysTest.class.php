<?php namespace lang\ast\unittest\emit;

class ArraysTest extends EmittingTest {

  #[@test]
  public function array_literal() {
    $r= $this->run('class <T> {
      public function run() {
        return [1, 2, 3];
      }
    }');

    $this->assertEquals([1, 2, 3], $r);
  }

  #[@test]
  public function map_literal() {
    $r= $this->run('class <T> {
      public function run() {
        return ["a" => 1, "b" => 2];
      }
    }');

    $this->assertEquals(['a' => 1, 'b' => 2], $r);
  }

  #[@test]
  public function append() {
    $r= $this->run('class <T> {
      public function run() {
        $r= [1, 2];
        $r[]= 3;
        return $r;
      }
    }');

    $this->assertEquals([1, 2, 3], $r);
  }
}