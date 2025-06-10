<?php namespace lang\ast\unittest\emit;

use test\{Assert, Before, Test, Values};

/** @see https://www.php.net/manual/en/language.oop5.cloning.php */
class CloningTest extends EmittingTest {
  private $fixture;

  /** @return iterable */
  private function arguments() {
    yield ['clone($in, ["id" => $this->id, "name" => "Changed"])'];
    yield ['clone($in, withProperties: ["id" => $this->id, "name" => "Changed"])'];
    yield ['clone(object: $in, withProperties: ["id" => $this->id, "name" => "Changed"])'];
    yield ['clone(withProperties: ["id" => $this->id, "name" => "Changed"], object: $in)'];
  }

  #[Before]
  public function fixture() {
    $this->fixture= new class() {
      private $id= 1;
      private $name= 'Test';

      public function toString() {
        return "<id: {$this->id}, name: {$this->name}>";
      }

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

    Assert::equals(
      ['<id: 1, name: Test>', '<id: 2, name: Test>'],
      [$this->fixture->toString(), $clone->toString()]
    );
  }

  #[Test, Values(from: 'arguments')]
  public function clone_with($expression) {
    $clone= $this->run('class %T {
      private $id= 6100;
      public function run($in) { return '.$expression.'; }
    }', $this->fixture->with(1));

    Assert::equals(
      ['<id: 1, name: Test>', '<id: 6100, name: Changed>'],
      [$this->fixture->toString(), $clone->toString()]
    );
  }

  #[Test]
  public function clone_with_named_argument() {
    $clone= $this->run('class %T {
      public function run($in) {
        return clone(object: $in);
      }
    }', $this->fixture->with(1));

    Assert::equals(
      ['<id: 1, name: Test>', '<id: 2, name: Test>'],
      [$this->fixture->toString(), $clone->toString()]
    );
  }
}