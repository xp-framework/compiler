<?php namespace lang\ast\unittest\emit;

use test\{Assert, Test, Values};

class OperatorTest extends EmittingTest {

  #[Test, Values([['+=', 3], ['-=', -1], ['*=', 2], ['/=', 0.5], ['**=', 1], ['%=', 1]])]
  public function assignment_and_math($op, $expected) {
    $r= $this->run('class %T {
      public function run() {
        $a= 1;
        $a'.$op.' 2;
        return $a;
      }
    }');

    Assert::equals($expected, $r);
  }

  #[Test, Values([['|=', 0x0003], ['&=', 0x0002], ['^=', 0x0001], ['>>=', 0x0000], ['<<=', 0x000C]])]
  public function assignment_and_bitwise($op, $expected) {
    $r= $this->run('class %T {
      public function run() {
        $a= 0x0003;
        $a'.$op.' 0x0002;
        return $a;
      }
    }');

    Assert::equals($expected, $r);
  }

  #[Test]
  public function concatenation() {
    $r= $this->run('class %T {
      public function run() {
        $a= "A..";
        $a.= "B";
        return $a;
      }
    }');

    Assert::equals('A..B', $r);
  }

  #[Test, Values([['$a++', 2, 1], ['++$a', 2, 2], ['$a--', 0, 1], ['--$a', 0, 0]])]
  public function inc_dec($op, $a, $b) {
    $r= $this->run('class %T {
      public function run() {
        $a= 1;
        $b= '.$op.';
        return [$a, $b];
      }
    }');

    Assert::equals([$a, $b], $r);
  }

  #[Test]
  public function references() {
    $r= $this->run('class %T {
      public function run() {
        $a= 3;
        $ptr= &$a;
        $a++;
        return $ptr;
      }
    }');

    Assert::equals(4, $r);
  }

  #[Test]
  public function destructuring() {
    $r= $this->run('class %T {
      public function run() {
        [$a, $b]= explode("..", "A..B");
        return [$a, $b];
      }
    }');

    Assert::equals(['A', 'B'], $r);
  }

  #[Test]
  public function swap_variables() {
    $r= $this->run('class %T {
      public function run() {
        $a= 1; $b= 2;
        [$a, $b]= [$b, $a];
        return [$a, $b];
      }
    }');

    Assert::equals([2, 1], $r);
  }

  #[Test, Values([[null, true], [false, false], ['Test', 'Test']])]
  public function null_coalesce_assigns_true_if_null($value, $expected) {
    $r= $this->run('class %T {
      public function run($arg) {
        $arg??= true;
        return $arg;
      }
    }', $value);

    Assert::equals($expected, $r);
  }

  #[Test, Values([[[], true], [[null], true], [[false], false], [['Test'], 'Test']])]
  public function null_coalesce_fills_array_if_non_existant_or_null($value, $expected) {
    $r= $this->run('class %T {
      public function run($arg) {
        $arg[0]??= true;
        return $arg;
      }
    }', $value);

    Assert::equals($expected, $r[0]);
  }
}