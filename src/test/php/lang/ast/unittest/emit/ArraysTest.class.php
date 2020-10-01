<?php namespace lang\ast\unittest\emit;

use unittest\{Assert, Test};

class ArraysTest extends EmittingTest {

  #[Test]
  public function array_literal() {
    $r= $this->run('class <T> {
      public function run() {
        return [1, 2, 3];
      }
    }');

    Assert::equals([1, 2, 3], $r);
  }

  #[Test]
  public function map_literal() {
    $r= $this->run('class <T> {
      public function run() {
        return ["a" => 1, "b" => 2];
      }
    }');

    Assert::equals(['a' => 1, 'b' => 2], $r);
  }

  #[Test]
  public function append() {
    $r= $this->run('class <T> {
      public function run() {
        $r= [1, 2];
        $r[]= 3;
        return $r;
      }
    }');

    Assert::equals([1, 2, 3], $r);
  }

  #[Test]
  public function destructuring() {
    $r= $this->run('class <T> {
      public function run() {
        [$a, $b]= [1, 2];
        return [$a, $b];
      }
    }');

    Assert::equals([1, 2], $r);
  }

  #[Test]
  public function init_with_variable() {
    $r= $this->run('class <T> {
      public function run() {
        $KEY= "key";
        return [$KEY => "value"];
      }
    }');

    Assert::equals(['key' => 'value'], $r);
  }

  #[Test]
  public function init_with_member_variable() {
    $r= $this->run('class <T> {
      private static $KEY= "key";
      public function run() {
        return [self::$KEY => "value"];
      }
    }');

    Assert::equals(['key' => 'value'], $r);
  }
}