<?php namespace lang\ast\unittest\emit;

class UnicodeEscapesTest extends EmittingTest {

  #[@test]
  public function spanish_ñ() {
    $r= $this->run('class <T> {
      public function run() {
        return "ma\u{00F1}ana";
      }
    }');

    $this->assertEquals('mañana', $r);
  }

  #[@test]
  public function emoji() {
    $r= $this->run('class <T> {
      public function run() {
        return "Smile! \u{1F602}";
      }
    }');

    $this->assertEquals('Smile! 😂', $r);
  }
}