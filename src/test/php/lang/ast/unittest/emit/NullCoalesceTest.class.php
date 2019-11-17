<?php namespace lang\ast\unittest\emit;

use unittest\Assert;
use unittest\actions\RuntimeVersion;

class NullCoalesceTest extends EmittingTest {

  #[@test, @action(new RuntimeVersion('>=7.0'))]
  public function on_null() {
    $r= $this->run('class <T> {
      public function run() {
        return null ?? true;
      }
    }');

    Assert::true($r);
  }

  #[@test]
  public function on_unset_array_key() {
    $r= $this->run('class <T> {
      public function run() {
        return $array["key"] ?? true;
      }
    }');

    Assert::true($r);
  }

  #[@test]
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