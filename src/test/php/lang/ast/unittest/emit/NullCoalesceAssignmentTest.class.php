<?php namespace lang\ast\unittest\emit;

use unittest\{Assert, Test, Values};

class NullCoalesceAssignmentTest extends EmittingTest {

  #[Test, Values([[null, true], [false, false], ['Test', 'Test']])]
  public function assigns_true_if_null($value, $expected) {
    $r= $this->run('class <T> {
      public function run($arg) {
        $arg??= true;
        return $arg;
      }
    }', $value);

    Assert::equals($expected, $r);
  }

  #[Test, Values([[[], [true]], [[null], [true]]])]
  public function fills_array_if_non_existant_or_null($value, $expected) {
    $r= $this->run('class <T> {
      public function run($arg) {
        $arg[0]??= true;
        return $arg;
      }
    }', $value);

    Assert::equals($expected, $r);
  }
}