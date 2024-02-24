<?php namespace lang\ast\unittest\emit;

use lang\{IllegalArgumentException, Reflection};
use test\{Assert, Test, Values};

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
    yield ['new FileInput(new Handle(0))', new FileInput(new Handle(0))];
    yield ['[new Handle(0)]', [new Handle(0)]];
  }

  #[Test, Values(from: 'expressions')]
  public function property($declaration, $expected) {
    Assert::equals($expected, $this->run(strtr('use lang\ast\unittest\emit\{FileInput, Handle}; class %T {
      const INITIAL= "initial";
      private $h= %D;

      public function run() {
        return $this->h;
      }
    }', ['%D' => $declaration])));
  }

  #[Test, Values(from: 'expressions')]
  public function reflective_access_to_property($declaration, $expected) {
    $t= $this->declare(strtr('use lang\ast\unittest\emit\{FileInput, Handle}; class %T {
      const INITIAL= "initial";
      public $h= %D;
    }', ['%D' => $declaration]));

    Assert::equals($expected, $t->property('h')->get($t->newInstance()));
  }

  #[Test, Values(['fn($arg) => $arg->redirect(1)', 'function($arg) { return $arg->redirect(1); }'])]
  public function using_closures($declaration) {
    $r= $this->run(strtr('class %T {
      private $h= %D;

      public function run() {
        return $this->h;
      }
    }', ['%D' => $declaration]));
    Assert::equals(new Handle(1), $r(new Handle(0)));
  }

  #[Test]
  public function using_closures_referencing_this() {
    $r= $this->run('use lang\ast\unittest\emit\Handle; class %T {
      private $id= 1;
      private $h= fn() => new Handle($this->id);

      public function run() {
        return $this->h;
      }
    }');
    Assert::equals(new Handle(1), $r());
  }

  #[Test]
  public function using_new_referencing_this() {
    $r= $this->run('use lang\ast\unittest\emit\Handle; class %T {
      private $id= 1;
      private $h= new Handle($this->id);

      public function run() {
        return $this->h;
      }
    }');
    Assert::equals(new Handle(1), $r);
  }

  #[Test]
  public function using_anonymous_classes() {
    $r= $this->run('class %T {
      private $h= new class() { public function pipe($h) { return $h->redirect(1); } };

      public function run() {
        return $this->h;
      }
    }');
    Assert::equals(new Handle(1), $r->pipe(new Handle(0)));
  }

  #[Test]
  public function property_initialization_accessible_inside_constructor() {
    $r= $this->run('use lang\ast\unittest\emit\Handle; class %T {
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
    $r= $this->run('use lang\ast\unittest\emit\Handle; class %T {
      public function __construct(private $h= new Handle(0)) { }

      public function run() {
        return $this->h;
      }
    }');
    Assert::equals(new Handle(0), $r);
  }

  #[Test]
  public function parameter_default_when_omitted() {
    $r= $this->run('use lang\ast\unittest\emit\Handle; class %T {
      public function run($h= new Handle(0)) {
        return $h;
      }
    }');
    Assert::equals(new Handle(0), $r);
  }

  #[Test]
  public function parameter_default_when_passed() {
    $r= $this->run('use lang\ast\unittest\emit\Handle; class %T {
      public function run($h= new Handle(0)) {
        return $h;
      }
    }', new Handle(1));
    Assert::equals(new Handle(1), $r);
  }

  #[Test]
  public function parameter_default_reflective_access() {
    $t= $this->declare('use lang\ast\unittest\emit\Handle; class %T {
      public function run($h= new Handle(0)) {
        // NOOP
      }
    }');
    Assert::equals(new Handle(0), $t->method('run')->parameter(0)->default());
  }

  #[Test]
  public function property_reference_as_parameter_default() {
    $r= $this->run('use lang\ast\unittest\emit\Handle; class %T {
      private static $h= new Handle(0);

      public function run($h= self::$h) {
        return $h;
      }
    }');
    Assert::equals(new Handle(0), $r);
  }

  #[Test]
  public function typed_proprety() {
    $r= $this->run('use lang\ast\unittest\emit\Handle; class %T {
      private Handle $h= new Handle(0);

      public function run() {
        return $this->h;
      }
    }');
    Assert::equals(new Handle(0), $r);
  }

  #[Test]
  public function typed_parameter() {
    $r= $this->run('use lang\ast\unittest\emit\Handle; class %T {
      public function run(Handle $h= new Handle(0)) {
        return $h;
      }
    }');
    Assert::equals(new Handle(0), $r);
  }

  #[Test]
  public function static_variable() {
    $r= $this->run('use lang\ast\unittest\emit\Handle; class %T {
      public function run() {
        static $h= new Handle(0);

        return $h;
      }
    }');
    Assert::equals(new Handle(0), $r);
  }

  #[Test]
  public function with_argument_promotion() {
    $t= $this->declare('use lang\ast\unittest\emit\Handle; class %T {
      private $h= new Handle(0);

      public function __construct(private Handle $p) { }

      public function run() {
        return $this->h->compareTo($this->p);
      }
    }');
    Assert::equals(1, $t->newInstance(new Handle(1))->run());
  }

  #[Test]
  public function invokes_parent_constructor() {
    $t= $this->declare('class %T {
      protected $invoked= false;

      public function __construct($invoked) {
        $this->invoked= $invoked;
      }
    }');

    $r= $this->declare('use lang\ast\unittest\emit\Handle; class %T extends '.$t->literal().' {
      private $h= new Handle(0);

      public function run() {
        return [$this->invoked, $this->h];
      }
    }');
    Assert::equals([true, new Handle(0)], $r->newInstance(true)->run());
  }
}