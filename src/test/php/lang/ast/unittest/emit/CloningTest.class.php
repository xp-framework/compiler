<?php namespace lang\ast\unittest\emit;

use test\{Assert, Before, Test};

/** @see https://www.php.net/manual/en/language.oop5.cloning.php */
class CloningTest extends EmittingTest {
  private $fixture;

  #[Before]
  public function fixture() {
    $this->fixture= new class() {
      public $id= 1;

      public function with($id) {
        $this->id= $id;
        return $this;
      }

      public function __clone() {
        $this->id++;
      }
    };
  }

  #[Test]
  public function clone_operator() {
    $clone= $this->run('class %T {
      public function run($in) {
        return clone $in;
      }
    }', $this->fixture);

    Assert::true($clone instanceof $this->fixture && $this->fixture !== $clone);
  }

  #[Test]
  public function clone_function() {
    $clone= $this->run('class %T {
      public function run($in) {
        return clone($in);
      }
    }', $this->fixture);

    Assert::true($clone instanceof $this->fixture && $this->fixture !== $clone);
  }

  #[Test]
  public function clone_interceptor_called() {
    $clone= $this->run('class %T {
      public function run($in) {
        return clone $in;
      }
    }', $this->fixture->with(1));

    Assert::equals([1, 2], [$this->fixture->id, $clone->id]);
  }
}