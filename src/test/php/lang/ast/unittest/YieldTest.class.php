<?php namespace lang\ast\unittest;

class YieldTest extends EmittingTest {

  #[@test]
  public function yield_values() {
    $r= $this->run('class <T> {
      public function run() {
        yield 1;
        yield 2;
        yield 3;
      }
    }');
    $this->assertEquals([1, 2, 3], iterator_to_array($r));
  }

  #[@test]
  public function yield_keys_and_values() {
    $r= $this->run('class <T> {
      public function run() {
        yield "color" => "orange";
        yield "price" => 12.99;
      }
    }');
    $this->assertEquals(['color' => 'orange', 'price' => 12.99], iterator_to_array($r));
  }

  #[@test]
  public function yield_from_array() {
    $r= $this->run('class <T> {
      public function run() {
        yield from [1, 2, 3];
      }
    }');
    $this->assertEquals([1, 2, 3], iterator_to_array($r));
  }

  #[@test]
  public function yield_from_generator() {
    $r= $this->run('class <T> {
      private function values() {
        yield 1;
        yield 2;
        yield 3;
      }

      public function run() {
        yield from $this->values();
      }
    }');
    $this->assertEquals([1, 2, 3], iterator_to_array($r));
  }
}