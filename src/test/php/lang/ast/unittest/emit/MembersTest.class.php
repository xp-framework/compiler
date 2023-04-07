<?php namespace lang\ast\unittest\emit;

use lang\ArrayType;
use test\{Assert, Ignore, Test, Values};

class MembersTest extends EmittingTest {

  #[Test]
  public function class_property() {
    $r= $this->run('class <T> {
      private static $MEMBER= "Test";

      public function run() {
        return self::$MEMBER;
      }
    }');

    Assert::equals('Test', $r);
  }

  #[Test]
  public function typed_class_property() {
    $r= $this->run('class <T> {
      private static string $MEMBER= "Test";

      public function run() {
        return self::$MEMBER;
      }
    }');

    Assert::equals('Test', $r);
  }

  #[Test]
  public function class_method() {
    $r= $this->run('class <T> {
      private static function member() { return "Test"; }

      public function run() {
        return self::member();
      }
    }');

    Assert::equals('Test', $r);
  }

  #[Test]
  public function class_constant() {
    $r= $this->run('class <T> {
      private const MEMBER = "Test";

      public function run() {
        return self::MEMBER;
      }
    }');

    Assert::equals('Test', $r);
  }

  #[Test]
  public function typed_class_constant() {
    $r= $this->run('class <T> {
      private const string MEMBER = "Test";

      public function run() {
        return self::MEMBER;
      }
    }');

    Assert::equals('Test', $r);
  }

  #[Test, Values(['$this->$member', '$this->{$member}'])]
  public function dynamic_instance_property($syntax) {
    $r= $this->run('class <T> {
      private $MEMBER= "Test";

      public function run() {
        $member= "MEMBER";
        return '.$syntax.';
      }
    }');

    Assert::equals('Test', $r);
  }

  #[Test, Ignore('Unsupported!')]
  public function dynamic_class_property() {
    $r= $this->run('class <T> {
      private static $MEMBER= "Test";

      public function run() {
        $member= "MEMBER";
        return self::${$member};
      }
    }');

    Assert::equals('Test', $r);
  }

  #[Test, Values(['$this->$method()', '$this->{$method}()'])]
  public function dynamic_instance_method($syntax) {
    $r= $this->run('class <T> {
      private function test() { return "Test"; }

      public function run() {
        $method= "test";
        return '.$syntax.';
      }
    }');

    Assert::equals('Test', $r);
  }

  #[Test, Values(['self::$method()', 'self::{$method}()'])]
  public function dynamic_class_method($syntax) {
    $r= $this->run('class <T> {
      private static function test() { return "Test"; }

      public function run() {
        $method= "test";
        return '.$syntax.';
      }
    }');

    Assert::equals('Test', $r);
  }

  #[Test, Ignore('Unsupported!')]
  public function dynamic_class_constant() {
    $r= $this->run('class <T> {
      const MEMBER= "Test";

      public function run() {
        $member= "MEMBER";
        return self::{$member};
      }
    }');

    Assert::equals('Test', $r);
  }

  #[Test]
  public function property_of_dynamic_class() {
    $r= $this->run('class <T> {
      private static $MEMBER= "Test";

      public function run() {
        $class= self::class;
        return $class::$MEMBER;
      }
    }');

    Assert::equals('Test', $r);
  }

  #[Test]
  public function method_of_dynamic_class() {
    $r= $this->run('class <T> {
      private static function member() { return "Test"; }

      public function run() {
        $class= self::class;
        return $class::member();
      }
    }');

    Assert::equals('Test', $r);
  }

  #[Test]
  public function constant_of_dynamic_class() {
    $r= $this->run('class <T> {
      private const MEMBER = "Test";

      public function run() {
        $class= self::class;
        return $class::MEMBER;
      }
    }');

    Assert::equals('Test', $r);
  }

  #[Test]
  public function object_class_constant() {
    $r= $this->run('class <T> {
      private const MEMBER = "Test";

      public function run() {
        return $this::MEMBER;
      }
    }');

    Assert::equals('Test', $r);
  }

  #[Test]
  public function list_property() {
    $r= $this->run('class <T> {
      private $list= [1, 2, 3];

      public function run() {
        return $this->list;
      }
    }');

    Assert::equals([1, 2, 3], $r);
  }

  #[Test]
  public function list_method() {
    $r= $this->run('class <T> {
      private function list() { return [1, 2, 3]; }

      public function run() {
        return $this->list();
      }
    }');

    Assert::equals([1, 2, 3], $r);
  }

  #[Test, Values(['variable', 'invocation', 'array'])]
  public function class_on_objects($via) {
    $t= $this->type('class <T> {
      private function this() { return $this; }

      public function variable() { return $this::class; }

      public function invocation() { return $this->this()::class; }

      public function array() { return [$this][0]::class; }
    }');

    $fixture= $t->newInstance();
    Assert::equals(get_class($fixture), $t->getMethod($via)->invoke($fixture));
  }

  #[Test]
  public function instance_property() {
    $r= $this->run('class <T> {
      private $member= "Test";

      public function run() {
        return $this->member;
      }
    }');

    Assert::equals('Test', $r);
  }

  #[Test]
  public function typed_instance_property() {
    $r= $this->run('class <T> {
      private string $member= "Test";

      public function run() {
        return $this->member;
      }
    }');

    Assert::equals('Test', $r);
  }

  #[Test]
  public function instance_method() {
    $r= $this->run('class <T> {
      private function member() { return "Test"; }

      public function run() {
        return $this->member();
      }
    }');

    Assert::equals('Test', $r);
  }

  #[Test]
  public function static_initializer_run() {
    $r= $this->run('class <T> {
      private static $MEMBER;

      static function __static() {
        self::$MEMBER= "Test";
      }

      public function run() {
        return self::$MEMBER;
      }
    }');

    Assert::equals('Test', $r);
  }

  #[Test]
  public function enum_members() {
    $r= $this->run('class <T> extends \lang\Enum {
      public static $MON, $TUE, $WED, $THU, $FRI, $SAT, $SUN;

      public function run() {
        return self::$MON->name();
      }
    }');

    Assert::equals('MON', $r);
  }

  #[Test]
  public function allow_constant_syntax_for_members() {
    $r= $this->run('use lang\{Enum, CommandLine}; class <T> extends Enum {
      public static $MON, $TUE, $WED, $THU, $FRI, $SAT, $SUN;

      public function run() {
        return [self::MON->name(), <T>::TUE->name(), CommandLine::WINDOWS->name()];
      }
    }');

    Assert::equals(['MON', 'TUE', 'WINDOWS'], $r);
  }

  #[Test]
  public function method_with_static() {
    $r= $this->run('class <T> {
      public function run() {
        static $var= "Test";
        return $var;
      }
    }');

    Assert::equals('Test', $r);
  }

  #[Test]
  public function method_with_static_without_initializer() {
    $r= $this->run('class <T> {
      public function run() {
        static $var;
        return $var;
      }
    }');

    Assert::null($r);
  }

  #[Test]
  public function chaining_sccope_operators() {
    $r= $this->run('class <T> {
      private const TYPE = self::class;

      private const NAME = "Test";

      private static $name = "Test";

      private static function name() { return "Test"; }

      public function run() {
        $name= "name";
        return [self::TYPE::NAME, self::TYPE::$name, self::TYPE::name(), self::TYPE::$name()];
      }
    }');

    Assert::equals(['Test', 'Test', 'Test', 'Test'], $r);
  }

  #[Test]
  public function self_return_type() {
    $t= $this->type('
      class <T> { public function run(): self { return $this; } }
    ');
    Assert::equals($t, $t->getMethod('run')->getReturnType());
  }

  #[Test]
  public function static_return_type() {
    $t= $this->type('
      class <T>Base { public function run(): static { return $this; } }
      class <T> extends <T>Base { }
    ');
    Assert::equals($t, $t->getMethod('run')->getReturnType());
  }

  #[Test]
  public function array_of_self_return_type() {
    $t= $this->type('
      class <T> { public function run(): array<self> { return [$this]; } }
    ');
    Assert::equals(new ArrayType($t), $t->getMethod('run')->getReturnType());
  }
}