<?php namespace lang\ast\unittest\emit;

use unittest\Assert;

class ControlStructuresTest extends EmittingTest {

  #[@test, @values([
  #  [0, 'no items'],
  #  [1, 'one item'],
  #  [2, '2 items'],
  #  [3, '3 items'],
  #])]
  public function if_else_cascade($input, $expected) {
    $r= $this->run('class <T> {
      public function run($arg) {
        if (0 === $arg) {
          return "no items";
        } else if (1 === $arg) {
          return "one item";
        } else {
          return $arg." items";
        }
      }
    }', $input);

    Assert::equals($expected, $r);
  }

  #[@test, @values([
  #  [0, 'no items'],
  #  [1, 'one item'],
  #  [2, '2 items'],
  #  [3, '3 items'],
  #])]
  public function switch_case($input, $expected) {
    $r= $this->run('class <T> {
      public function run($arg) {
        switch ($arg) {
          case 0: return "no items";
          case 1: return "one item";
          default: return $arg." items";
        }
      }
    }', $input);

    Assert::equals($expected, $r);
  }

  #[@test, @values([[SEEK_SET, 10], [SEEK_CUR, 11]])]
  public function switch_case_goto_label_ambiguity($whence, $expected) {
    $r= $this->run('class <T> {
      public function run($arg) {
        $position= 1;
        switch ($arg) {
          case SEEK_SET: $position= 10; break;
          case SEEK_CUR: $position+= 10; break;
        }
        return $position;
      }
    }', $whence);

    Assert::equals($expected, $r);
  }
}