<?php namespace lang\ast\unittest\emit;

use unittest\Assert;

class UnicodeEscapesTest extends EmittingTest {

  #[@test]
  public function spanish_ñ() {
    $r= $this->run('class <T> {
      public function run() {
        return "ma\u{00F1}ana";
      }
    }');

    Assert::equals('mañana', $r);
  }

  #[@test]
  public function emoji() {
    $r= $this->run('class <T> {
      public function run() {
        return "Smile! \u{1F602}";
      }
    }');

    Assert::equals('Smile! 😂', $r);
  }
}