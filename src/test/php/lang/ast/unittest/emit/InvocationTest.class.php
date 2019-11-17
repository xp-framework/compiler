<?php namespace lang\ast\unittest\emit;

use unittest\Assert;

class InvocationTest extends EmittingTest {

  #[@test]
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

  #[@test]
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

  #[@test]
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

  #[@test]
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

  #[@test]
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

  #[@test]
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

  #[@test]
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
}