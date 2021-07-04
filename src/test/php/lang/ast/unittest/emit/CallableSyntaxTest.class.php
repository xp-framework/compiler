<?php namespace lang\ast\unittest\emit;

use unittest\{Assert, Test, Values};

/**
 * Tests for first-class callable syntax
 *
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
  public function native_function() {
    $this->verify('class <T> {
      public function run() { return strlen(...); }
    }');
  }

  #[Test]
  public function instance_method() {
    $this->verify('class <T> {
      public function length($arg) { return strlen($arg); }
      public function run() { return $this->length(...); }
    }');
  }

  #[Test]
  public function class_method() {
    $this->verify('class <T> {
      public static function length($arg) { return strlen($arg); }
      public function run() { return self::length(...); }
    }');
  }

  #[Test]
  public function private_method() {
    $this->verify('class <T> {
      private function length($arg) { return strlen($arg); }
      public function run() { return $this->length(...); }
    }');
  }

  #[Test]
  public function string_reference() {
    $this->verify('class <T> {
      public function run() {
        $func= "strlen";
        return $func(...);
      }
    }');
  }

  #[Test]
  public function fn_reference() {
    $this->verify('class <T> {
      public function run() {
        $func= fn($arg) => strlen($arg);
        return $func(...);
      }
    }');
  }

  #[Test]
  public function instance_property_reference() {
    $this->verify('class <T> {
      private $func= "strlen";
      public function run() {
        return ($this->func)(...);
      }
    }');
  }

  #[Test, Values(['$this->$func(...)', '$this->{$func}(...)'])]
  public function variable_instance_method($expr) {
    $this->verify('class <T> {
      private function length($arg) { return strlen($arg); }
      public function run() {
        $func= "length";
        return '.$expr.';
      }
    }');
  }

  #[Test, Values(['self::$func(...)', 'self::{$func}(...)'])]
  public function variable_class_method($expr) {
    $this->verify('class <T> {
      private static function length($arg) { return strlen($arg); }
      public function run() {
        $func= "length";
        return '.$expr.';
      }
    }');
  }

  #[Test]
  public function variable_class_method_with_variable_class() {
    $this->verify('class <T> {
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
    $this->verify('class <T> {
      public function run() { return "strlen"(...); }
    }');
  }

  #[Test]
  public function array_instance_method_reference() {
    $this->verify('class <T> {
      public function length($arg) { return strlen($arg); }
      public function run() { return [$this, "length"](...); }
    }');
  }

  #[Test]
  public function array_class_method_reference() {
    $this->verify('class <T> {
      public static function length($arg) { return strlen($arg); }
      public function run() { return [self::class, "length"](...); }
    }');
  }
}