<?php namespace lang\ast\unittest\emit;

use test\verify\Runtime;
use test\{Action, Assert, Test, Values};

class InvocationTest extends EmittingTest {

  #[Test]
  public function instance_method() {
    Assert::equals('instance', $this->run(
      'class <T> {

        public function instanceMethod() { return "instance"; }

        public function run() {
          return $this->instanceMethod();
        }
      }'
    ));
  }

  #[Test]
  public function instance_method_dynamic_variable() {
    Assert::equals('instance', $this->run(
      'class <T> {

        public function instanceMethod() { return "instance"; }

        public function run() {
          $method= "instanceMethod";
          return $this->{$method}();
        }
      }'
    ));
  }

  #[Test]
  public function instance_method_dynamic_expression() {
    Assert::equals('instance', $this->run(
      'class <T> {

        public function instanceMethod() { return "instance"; }

        public function run() {
          $method= fn() => "instanceMethod";
          return $this->{$method()}();
        }
      }'
    ));
  }

  #[Test]
  public function static_method() {
    Assert::equals('static', $this->run(
      'class <T> {

        public function staticMethod() { return "static"; }

        public function run() {
          return self::staticMethod();
        }
      }'
    ));
  }

  #[Test]
  public function static_method_dynamic() {
    Assert::equals('static', $this->run(
      'class <T> {

        public static function staticMethod() { return "static"; }

        public function run() {
          $method= "staticMethod";
          return self::$method();
        }
      }'
    ));
  }

  #[Test]
  public function closure() {
    Assert::equals('closure', $this->run(
      'class <T> {

        public function run() {
          $f= function() { return "closure"; };
          return $f();
        }
      }'
    ));
  }

  #[Test]
  public function global_function() {
    Assert::equals('function', $this->run(
      'function fixture() { return "function"; }
      class <T> {

        public function run() {
          return fixture();
        }
      }'
    ));
  }

  #[Test]
  public function function_self_reference() {
    Assert::equals(13, $this->run(
      'class <T> {

        public function run() {
          $fib= function($i) use(&$fib) {
            if (0 === $i || 1 === $i) {
              return $i;
            } else {
              return $fib($i - 1) + $fib($i - 2);
            }
          };
          return $fib(7);
        }
      }'
    ));
  }

  #[Test, Values(['"html(<) = &lt;", flags: ENT_HTML5', '"html(<) = &lt;", ENT_HTML5, double: true', 'string: "html(<) = &lt;", flags: ENT_HTML5', 'string: "html(<) = &lt;", flags: ENT_HTML5, double: true',])]
  public function named_arguments_in_exact_order($arguments) {
    Assert::equals('html(&lt;) = &amp;lt;', $this->run(
      'class <T> {

        public function escape($string, $flags= ENT_HTML5, $double= true) {
          return htmlspecialchars($string, $flags, null, $double);
        }

        public function run() {
          return $this->escape('.$arguments.');
        }
      }'
    ));
  }

  #[Test, Runtime(php: '>=8.0')]
  public function named_arguments_in_reverse_order() {
    Assert::equals('html(&lt;) = &amp;lt;', $this->run(
      'class <T> {

        public function escape($string, $flags= ENT_HTML5, $double= true) {
          return htmlspecialchars($string, $flags, null, $double);
        }

        public function run() {
          return $this->escape(flags: ENT_HTML5, string: "html(<) = &lt;");
        }
      }'
    ));
  }

  #[Test, Runtime(php: '>=8.0')]
  public function named_arguments_omitting_one() {
    Assert::equals('html(&lt;) = &lt;', $this->run(
      'class <T> {

        public function escape($string, $flags= ENT_HTML5, $double= true) {
          return htmlspecialchars($string, $flags, null, $double);
        }

        public function run() {
          return $this->escape("html(<) = &lt;", double: false);
        }
      }'
    ));
  }
}