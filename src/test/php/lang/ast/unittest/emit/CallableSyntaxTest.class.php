<?php namespace lang\ast\unittest\emit;

use lang\Error;
use test\verify\Runtime;
use test\{Assert, Expect, Test, Values};

/**
 * Tests for first-class callable syntax
 *
 * @see   https://wiki.php.net/rfc/fcc_in_const_expr
 * @see   https://wiki.php.net/rfc/first_class_callable_syntax#proposal
 */
class CallableSyntaxTest extends EmittingTest {

  /**
   * Verification helper
   *
   * @param  string $code
   * @return void
   * @throws unittest.AssertionFailedError
   */
  private function verify($code) {
    Assert::equals(4, $this->run($code)('Test'));
  }

  #[Test]
  public function native_function_variadic() {
    $this->verify('class %T {
      public function run() { return strlen(...); }
    }');
  }

  #[Test]
  public function native_function_argument() {
    $this->verify('class %T {
      public function run() { return strlen(?); }
    }');
  }

  #[Test]
  public function instance_method() {
    $this->verify('class %T {
      public function length($arg) { return strlen($arg); }
      public function run() { return $this->length(...); }
    }');
  }

  #[Test]
  public function class_method() {
    $this->verify('class %T {
      public static function length($arg) { return strlen($arg); }
      public function run() { return self::length(...); }
    }');
  }

  #[Test]
  public function private_method() {
    $this->verify('class %T {
      private function length($arg) { return strlen($arg); }
      public function run() { return $this->length(...); }
    }');
  }

  #[Test]
  public function string_reference() {
    $this->verify('class %T {
      public function run() {
        $func= "strlen";
        return $func(...);
      }
    }');
  }

  #[Test]
  public function fn_reference() {
    $this->verify('class %T {
      public function run() {
        $func= fn($arg) => strlen($arg);
        return $func(...);
      }
    }');
  }

  #[Test]
  public function instance_property_reference() {
    $this->verify('class %T {
      private $func= "strlen";
      public function run() {
        return ($this->func)(...);
      }
    }');
  }

  #[Test, Values(['$this->$func(...)', '$this->{$func}(...)'])]
  public function variable_instance_method($expr) {
    $this->verify('class %T {
      private function length($arg) { return strlen($arg); }
      public function run() {
        $func= "length";
        return '.$expr.';
      }
    }');
  }

  #[Test, Values(['self::$func(...)', 'self::{$func}(...)'])]
  public function variable_class_method($expr) {
    $this->verify('class %T {
      private static function length($arg) { return strlen($arg); }
      public function run() {
        $func= "length";
        return '.$expr.';
      }
    }');
  }

  #[Test]
  public function variable_class_method_with_variable_class() {
    $this->verify('class %T {
      private static function length($arg) { return strlen($arg); }
      public function run() {
        $func= "length";
        $class= __CLASS__;
        return $class::$func(...);
      }
    }');
  }

  #[Test]
  public function string_function_reference() {
    $this->verify('class %T {
      public function run() { return "strlen"(...); }
    }');
  }

  #[Test]
  public function array_instance_method_reference() {
    $this->verify('class %T {
      public function length($arg) { return strlen($arg); }
      public function run() { return [$this, "length"](...); }
    }');
  }

  #[Test]
  public function array_class_method_reference() {
    $this->verify('class %T {
      public static function length($arg) { return strlen($arg); }
      public function run() { return [self::class, "length"](...); }
    }');
  }

  #[Test, Expect(Error::class), Values(['nonexistant', '$this->nonexistant', 'self::nonexistant', '$nonexistant', '$null'])]
  public function non_existant($expr) {
    $this->run('class %T {
      public function run() {
        $null= null;
        $nonexistant= "nonexistant";
        return '.$expr.'(...);
      }
    }');
  }

  #[Test]
  public function instantiation() {
    $f= $this->run('use lang\ast\unittest\emit\Handle; class %T {
      public function run() {
        return new Handle(...);
      }
    }');
    Assert::equals(new Handle(1), $f(1));
  }

  #[Test]
  public function instantiation_in_map() {
    $r= $this->run('use lang\ast\unittest\emit\Handle; class %T {
      public function run() {
        return array_map(new Handle(...), [0, 1, 2]);
      }
    }');
    Assert::equals([new Handle(0), new Handle(1), new Handle(2)], $r);
  }

  #[Test]
  public function variable_instantiation() {
    $r= $this->run('use lang\ast\unittest\emit\Handle; class %T {
      public function run() {
        $class= Handle::class;
        return array_map(new $class(...), [0, 1, 2]);
      }
    }');
    Assert::equals([new Handle(0), new Handle(1), new Handle(2)], $r);
  }

  #[Test]
  public function expression_instantiation() {
    $r= $this->run('use lang\ast\unittest\emit\Handle; class %T {
      public function run() {
        $version= "";
        return array_map(new (Handle::class.$version)(...), [0, 1, 2]);
      }
    }');
    Assert::equals([new Handle(0), new Handle(1), new Handle(2)], $r);
  }

  #[Test]
  public function anonymous_instantiation() {
    $f= $this->run('class %T {
      public function run() {
        return new class(...) {
          public $value;
          public function __construct($value) { $this->value= $value; }
        };
      }
    }');
    Assert::equals($this, $f($this)->value);
  }

  #[Test]
  public function inside_annotation() {
    $f= $this->run('use lang\Reflection; class %T {

      #[Attr(strrev(...))]
      public function run() {
        return Reflection::of($this)->method("run")->annotation(Attr::class)->argument(0);
      }
    }');
    Assert::equals('cba', $f('abc'));
  }

  #[Test]
  public function partial_function_application() {
    $f= $this->run('class %T {
      public function run() {
        return str_replace("test", "ok", ?);
      }
    }');
    Assert::equals('ok', $f('test'));
  }

  #[Test]
  public function partial_function_application_multiple_arguments() {
    $f= $this->run('class %T {
      public function run() {
        return str_replace("test", ?, ?);
      }
    }');
    Assert::equals('ok', $f('ok', 'test'));
  }

  #[Test]
  public function partial_function_application_variadic() {
    $f= $this->run('class %T {
      public function run() {
        return str_replace("test", ...);
      }
    }');
    Assert::equals('ok', $f('ok', 'test'));
  }

  #[Test]
  public function partial_function_application_callable_syntax_mixed() {
    $f= $this->run('class %T {
      public function run() {
        return array_map(strtoupper(...), ?);
      }
    }');
    Assert::equals(['ONE', 'TWO'], $f(['One', 'Two']));
  }

  #[Test, Runtime(php: '>=8.5.0')]
  public function partial_function_application_variadic_optional_by_ref() {
    $f= $this->run('class %T {
      public function run() {
        return str_replace("test", ...);
      }
    }');

    $count= 0;
    Assert::equals('ok', $f('ok', 'test', $count));
    Assert::equals(1, $count);
  }

  #[Test]
  public function partial_function_application_order() {
    [$result, $invokations]= $this->run('class %T {
      private $invokations= [];

      private function concat(... $args) {
        $this->invokations[]= __FUNCTION__;
        return implode("", $args);
      }

      private function arg() {
        $this->invokations[]= __FUNCTION__;
        return "ed";
      }

      public function run() {
        $f= $this->concat(?, $this->arg());
        $this->invokations[]= __FUNCTION__;
        return [$f("test"), $this->invokations];
      }
    }');
    Assert::equals('tested', $result);
    Assert::equals(['arg', 'run', 'concat'], $invokations);
  }
}