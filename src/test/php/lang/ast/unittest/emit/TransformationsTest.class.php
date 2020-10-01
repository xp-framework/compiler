<?php namespace lang\ast\unittest\emit;

use lang\ast\nodes\{Method, Signature};
use lang\ast\{Code, Type};
use unittest\{Assert, Before, Test, Values};

class TransformationsTest extends EmittingTest {

  /** @return void */
  #[Before]
  public function setUp() {
    $this->transform('class', function($codegen, $class) {
      if ($class->annotation('Repr')) {
        $class->declare(new Method(
          ['public'],
          'toString',
          new Signature([], new Type('string')),
          [new Code('return "T@".\util\Objects::stringOf(get_object_vars($this))')]
        ));
      }
      return $class;
    });
    $this->transform('class', function($codegen, $class) {
      if ($class->annotation('Getters')) {
        foreach ($class->properties() as $property) {
          $class->declare(new Method(
            ['public'],
            $property->name,
            new Signature([], $property->type),
            [new Code('return $this->'.$property->name)]
          ));
        }
      }
      return $class;
    });
  }

  #[Test]
  public function leaves_class_without_annotations() {
    $t= $this->type('class <T> {
      private int $id;

      public function __construct(int $id) {
        $this->id= $id;
      }
    }');
    Assert::false($t->hasMethod('id'));
  }

  #[Test]
  public function generates_string_representation() {
    $t= $this->type('#[Repr] class <T> {
      private int $id;

      public function __construct(int $id) {
        $this->id= $id;
      }
    }');
    Assert::true($t->hasMethod('toString'));
    Assert::equals("T@[\n  id => 1\n]", $t->getMethod('toString')->invoke($t->newInstance(1)));
  }

  #[Test, Values([['id', 1], ['name', 'Test']])]
  public function generates_accessor($name, $expected) {
    $t= $this->type('#[Getters] class <T> {
      private int $id;
      private string $name;

      public function __construct(int $id, string $name) {
        $this->id= $id;
        $this->name= $name;
      }
    }');
    Assert::true($t->hasMethod($name));
    Assert::equals($expected, $t->getMethod($name)->invoke($t->newInstance(1, 'Test')));
  }

  #[Test]
  public function generates_both() {
    $t= $this->type('#[Repr, Getters] class <T> {
      private int $id;

      public function __construct(int $id) {
        $this->id= $id;
      }
    }');
    Assert::equals(1, $t->getMethod('id')->invoke($t->newInstance(1)));
    Assert::equals("T@[\n  id => 1\n]", $t->getMethod('toString')->invoke($t->newInstance(1)));
  }
}