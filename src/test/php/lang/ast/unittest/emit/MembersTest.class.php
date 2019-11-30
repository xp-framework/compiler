<?php namespace lang\ast\unittest\emit;

use unittest\Assert;

class MembersTest extends EmittingTest {

  #[@test]
  public function class_property() {
    $r= $this->run('class <T> {
      private static $MEMBER= "Test";

      public function run() {
        return self::$MEMBER;
      }
    }');

    Assert::equals('Test', $r);
  }

  #[@test]
  public function class_method() {
    $r= $this->run('class <T> {
      private static function member() { return "Test"; }

      public function run() {
        return self::member();
      }
    }');

    Assert::equals('Test', $r);
  }

  #[@test]
  public function instance_property() {
    $r= $this->run('class <T> {
      private $member= "Test";

      public function run() {
        return $this->member;
      }
    }');

    Assert::equals('Test', $r);
  }

  #[@test]
  public function instance_method() {
    $r= $this->run('class <T> {
      private function member() { return "Test"; }

      public function run() {
        return $this->member();
      }
    }');

    Assert::equals('Test', $r);
  }

  #[@test]
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

  #[@test]
  public function enum_members() {
    $r= $this->run('class <T> extends \lang\Enum {
      public static $MON, $TUE, $WED, $THU, $FRI, $SAT, $SUN;

      public function run() {
        return self::$MON->name();
      }
    }');

    Assert::equals('MON', $r);
  }

  #[@test]
  public function method_with_static() {
    $r= $this->run('class <T> {
      public function run() {
        static $var= "Test";
        return $var;
      }
    }');

    Assert::equals('Test', $r);
  }

  #[@test]
  public function method_with_static_without_initializer() {
    $r= $this->run('class <T> {
      public function run() {
        static $var;
        return $var;
      }
    }');

    Assert::null($r);
  }
}