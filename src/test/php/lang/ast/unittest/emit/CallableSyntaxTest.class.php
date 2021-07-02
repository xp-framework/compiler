<?php namespace lang\ast\unittest\emit;

use unittest\actions\RuntimeVersion;
use unittest\{Action, Assert, Test, Values};

class CallableSyntaxTest extends EmittingTest {

  #[Test]
  public function native_function() {
    $f= $this->run('class <T> {
      public function run() { return strlen(...); }
    }');

    Assert::equals(4, $f('Test'));
  }

  #[Test]
  public function instance_method() {
    $f= $this->run('class <T> {
      public function length($arg) { return strlen($arg); }
      public function run() { return $this->length(...); }
    }');

    Assert::equals(4, $f('Test'));
  }

  #[Test]
  public function class_method() {
    $f= $this->run('class <T> {
      public static function length($arg) { return strlen($arg); }
      public function run() { return self::length(...); }
    }');

    Assert::equals(4, $f('Test'));
  }

  #[Test]
  public function private_method() {
    $f= $this->run('class <T> {
      private function length($arg) { return strlen($arg); }
      public function run() { return $this->length(...); }
    }');

    Assert::equals(4, $f('Test'));
  }

  #[Test]
  public function variable_function() {
    $f= $this->run('class <T> {
      public function run() {
        $func= "strlen";
        return $func(...);
      }
    }');

    Assert::equals(4, $f('Test'));
  }

  #[Test]
  public function instance_method_reference() {
    $f= $this->run('class <T> {
      private $func= "strlen";
      public function run() {
        return ($this->func)(...);
      }
    }');

    Assert::equals(4, $f('Test'));
  }

  #[Test]
  public function variable_instance_method() {
    $f= $this->run('class <T> {
      private function length($arg) { return strlen($arg); }
      public function run() {
        $func= "length";
        return $this->$func(...);
      }
    }');

    Assert::equals(4, $f('Test'));
  }

  #[Test]
  public function variable_class_method() {
    $f= $this->run('class <T> {
      private static function length($arg) { return strlen($arg); }
      public function run() {
        $func= "length";
        return self::$func(...);
      }
    }');

    Assert::equals(4, $f('Test'));
  }

  #[Test]
  public function variable_class_method_with_variable_class() {
    $f= $this->run('class <T> {
      private static function length($arg) { return strlen($arg); }
      public function run() {
        $func= "length";
        $class= __CLASS__;
        return $class::$func(...);
      }
    }');

    Assert::equals(4, $f('Test'));
  }

  #[Test]
  public function string_function_reference() {
    $f= $this->run('class <T> {
      public function run() { return "strlen"(...); }
    }');

    Assert::equals(4, $f('Test'));
  }

  #[Test]
  public function array_instance_method_reference() {
    $f= $this->run('class <T> {
      public function length($arg) { return strlen($arg); }
      public function run() { return [$this, "length"](...); }
    }');

    Assert::equals(4, $f('Test'));
  }

  #[Test]
  public function array_class_method_reference() {
    $f= $this->run('class <T> {
      public static function length($arg) { return strlen($arg); }
      public function run() { return [self::class, "length"](...); }
    }');

    Assert::equals(4, $f('Test'));
  }
}