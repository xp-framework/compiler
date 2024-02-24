<?php namespace lang\ast\unittest\emit;

use test\{Assert, Test, Values};

/** @see https://github.com/xp-framework/ast/pull/49 */
class LineNumbersTest extends EmittingTest {

  #[Test]
  public function method() {
    $r= $this->run('class %T {
      public function run() {
        return __LINE__;
      }
    }');
    Assert::equals(3, $r);
  }

  #[Test]
  public function array() {
    $r= $this->run('class %T {
      public function run() {
        return [
          __LINE__,
          __LINE__,
        ];
      }
    }');
    Assert::equals([4, 5], $r);
  }

  #[Test]
  public function addition() {
    $r= $this->run('class %T {
      private $lines= [];

      private function line($l) {
        $this->lines[]= $l;
        return 0;
      }

      public function run() {
        $r=
          $this->line(__LINE__) +
          $this->line(__LINE__)
        ;
        return $this->lines;
      }
    }');
    Assert::equals([11, 12], $r);
  }

  #[Test]
  public function chain() {
    $r= $this->run('class %T {
      private $lines= [];

      private function line($l) {
        $this->lines[]= $l;
        return $this;
      }

      public function run() {
        return $this
          ->line(__LINE__)
          ->line(__LINE__)
          ->lines
        ;
      }
    }');
    Assert::equals([11, 12], $r);
  }

  #[Test, Values([[true, 4], [false, 5]])]
  public function ternary($arg, $line) {
    $r= $this->run('class %T {
      public function run($arg) {
        return $arg
          ? __LINE__
          : __LINE__
        ;
      }
    }', $arg);
    Assert::equals($line, $r);
  }

  #[Test]
  public function binary() {
    $r= $this->run('class %T {
      private $lines= [];

      private function line($l, $r) {
        $this->lines[]= $l;
        return $r;
      }

      public function run() {
        $r= (
          $this->line(__LINE__, true) &&
          $this->line(__LINE__, false) ||
          $this->line(__LINE__, true)
        );
        return $this->lines;
      }
    }');
    Assert::equals([11, 12, 13], $r);
  }

  #[Test]
  public function arguments() {
    $r= $this->run('class %T {
      private function lines(... $lines) {
        return $lines;
      }

      public function run() {
        return $this->lines(
          __LINE__,
          __LINE__,
        );
      }
    }');
    Assert::equals([8, 9], $r);
  }
}