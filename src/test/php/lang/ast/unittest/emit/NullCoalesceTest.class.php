<?php namespace lang\ast\unittest\emit;

use test\{Action, Assert, Test};

class NullCoalesceTest extends EmittingTest {

  #[Test]
  public function on_null() {
    $r= $this->run('class <T> {
      public function run() {
        return null ?? true;
      }
    }');

    Assert::true($r);
  }

  #[Test]
  public function on_unset_array_key() {
    $r= $this->run('class <T> {
      public function run() {
        return $array["key"] ?? true;
      }
    }');

    Assert::true($r);
  }

  #[Test]
  public function assignment_operator() {
    $r= $this->run('class <T> {
      public function run() {
        $array["key"] ??= true;
        return $array;
      }
    }');

    Assert::equals(['key' => true], $r);
  }
}