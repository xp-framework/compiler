<?php namespace lang\ast\unittest\emit;

use lang\IllegalArgumentException;
use unittest\{Assert, Test, Values};

/**
 * Initialize parameters and properties with arbitrary expressions
 *
 * @see  https://github.com/xp-framework/compiler/pull/104
 * @see  https://wiki.php.net/rfc/new_in_initializers
 * @see  https://wiki.php.net/rfc/const_scalar_exprs
 * @see  https://wiki.php.net/rfc/calls_in_constant_expressions
 */
class InitializeWithExpressionsTest extends EmittingTest {

  /** @return iterable */
  private function expressions() {
    yield ['"test"', 'test'];
    yield ['[1, 2, 3]', [1, 2, 3]];
    yield ['1 << 2', 1 << 2];
    yield ['strlen("Test")', strlen('Test')];
    yield ['MODIFIER_PUBLIC', MODIFIER_PUBLIC];
    yield ['self::INITIAL', 'initial'];
    yield ['Handle::$DEFAULT', Handle::$DEFAULT];
    yield ['new Handle(0)', new Handle(0)];
    yield ['[new Handle(0)]', [new Handle(0)]];
  }

  #[Test, Values('expressions')]
  public function property($code, $expected) {
    Assert::equals($expected, $this->run(sprintf('use lang\ast\unittest\emit\Handle; class <T> {
      const INITIAL= "initial";
      private $h= %s;

      public function run() {
        return $this->h;
      }
    }', $code)));
  }

  #[Test]
  public function using_functions() {
    $r= $this->run('class <T> {
      private $h= fn($arg) => $arg->redirect(1);

      public function run() {
        return $this->h;
      }
    }');
    Assert::equals(new Handle(1), $r(new Handle(0)));
  }

  #[Test]
  public function using_anonymous_classes() {
    $r= $this->run('class <T> {
      private $h= new class() { public function pipe($h) { return $h->redirect(1); } };

      public function run() {
        return $this->h;
      }
    }');
    Assert::equals(new Handle(1), $r->pipe(new Handle(0)));
  }


  #[Test]
  public function property_initialization_accessible_inside_constructor() {
    $r= $this->run('use lang\ast\unittest\emit\Handle; class <T> {
      private $h= new Handle(0);

      public function __construct() {
        $this->h->redirect(1);
      }

      public function run() {
        return $this->h;
      }
    }');
    Assert::equals(new Handle(1), $r);
  }

  #[Test]
  public function promoted_property() {
    $r= $this->run('use lang\ast\unittest\emit\Handle; class <T> {
      public function __construct(private $h= new Handle(0)) { }

      public function run() {
        return $this->h;
      }
    }');
    Assert::equals(new Handle(0), $r);
  }

  #[Test]
  public function parameter_default_when_omitted() {
    $r= $this->run('use lang\ast\unittest\emit\Handle; class <T> {
      public function run($h= new Handle(0)) {
        return $h;
      }
    }');
    Assert::equals(new Handle(0), $r);
  }

  #[Test]
  public function parameter_default_when_passed() {
    $r= $this->run('use lang\ast\unittest\emit\Handle; class <T> {
      public function run($h= new Handle(0)) {
        return $h;
      }
    }', new Handle(1));
    Assert::equals(new Handle(1), $r);
  }

  #[Test]
  public function property_reference_as_parameter_default() {
    $r= $this->run('use lang\ast\unittest\emit\Handle; class <T> {
      private static $h= new Handle(0);

      public function run($h= self::$h) {
        return $h;
      }
    }');
    Assert::equals(new Handle(0), $r);
  }

  #[Test]
  public function typed_proprety() {
    $r= $this->run('use lang\ast\unittest\emit\Handle; class <T> {
      private Handle $h= new Handle(0);

      public function run() {
        return $this->h;
      }
    }');
    Assert::equals(new Handle(0), $r);
  }

  #[Test]
  public function typed_parameter() {
    $r= $this->run('use lang\ast\unittest\emit\Handle; class <T> {
      public function run(Handle $h= new Handle(0)) {
        return $h;
      }
    }');
    Assert::equals(new Handle(0), $r);
  }

  #[Test]
  public function static_variable() {
    $r= $this->run('use lang\ast\unittest\emit\Handle; class <T> {
      public function run() {
        static $h= new Handle(0);

        return $h;
      }
    }');
    Assert::equals(new Handle(0), $r);
  }
}