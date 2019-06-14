<?php namespace lang\ast\unittest\emit;

class NullCoalesceTest extends EmittingTest {

  #[@test]
  public function on_null() {
    $r= $this->run('class <T> {
      public function run() {
        return null ?? true;
      }
    }');

    $this->assertTrue($r);
  }

  #[@test]
  public function on_unset_array_key() {
    $r= $this->run('class <T> {
      public function run() {
        return $array["key"] ?? true;
      }
    }');

    $this->assertTrue($r);
  }

  #[@test]
  public function assignment_operator() {
    $r= $this->run('class <T> {
      public function run() {
        $array["key"] ??= true;
        return $array;
      }
    }');

    $this->assertEquals(['key' => true], $r);
  }
}