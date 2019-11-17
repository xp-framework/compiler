<?php namespace lang\ast\unittest\emit;

use unittest\Assert;
/** @see http://php.net/manual/en/language.generators.syntax.php */
class YieldTest extends EmittingTest {

  #[@test]
  public function yield_without_argument() {
    $r= $this->run('class <T> {
      public function run() {
        yield;
        yield;
      }
    }');
    Assert::equals([null, null], iterator_to_array($r));
  }

  #[@test]
  public function yield_values() {
    $r= $this->run('class <T> {
      public function run() {
        yield 1;
        yield 2;
        yield 3;
      }
    }');
    Assert::equals([1, 2, 3], iterator_to_array($r));
  }

  #[@test]
  public function yield_keys_and_values() {
    $r= $this->run('class <T> {
      public function run() {
        yield "color" => "orange";
        yield "price" => 12.99;
      }
    }');
    Assert::equals(['color' => 'orange', 'price' => 12.99], iterator_to_array($r));
  }

  #[@test]
  public function yield_from_array() {
    $r= $this->run('class <T> {
      public function run() {
        yield from [1, 2, 3];
      }
    }');
    Assert::equals([1, 2, 3], iterator_to_array($r));
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
    Assert::equals([1, 2, 3], iterator_to_array($r));
  }

  #[@test]
  public function yield_from_and_yield() {
    $r= $this->run('class <T> {
      public function run() {
        yield 1;
        yield from [2, 3];
        yield 4;
      }
    }');
    Assert::equals([1, 2, 3, 4], iterator_to_array($r, false));
  }
}