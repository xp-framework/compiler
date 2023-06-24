<?php namespace lang\ast\unittest\emit;

use lang\ast\Code;
use lang\ast\nodes\{Method, Signature};
use lang\ast\types\IsLiteral;
use test\{Assert, Before, Test, Values};

class TransformationsTest extends EmittingTest {

  /** @return void */
  #[Before]
  public function setUp() {
    $this->transform('class', function($codegen, $class) {
      if ($class->annotation('Repr')) {
        $class->declare(new Method(
          ['public'],
          'toString',
          new Signature([], new IsLiteral('string')),
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
    $t= $this->declare('class %T {
      private int $id;

      public function __construct(int $id) {
        $this->id= $id;
      }
    }');
    Assert::equals(null, $t->method('id'));
  }

  #[Test]
  public function generates_string_representation() {
    $t= $this->declare('#[Repr] class %T {
      private int $id;
      private string $name;

      public function __construct(int $id, string $name) {
        $this->id= $id;
        $this->name= $name;
      }
    }');
    Assert::notEquals(null, $t->method('toString'));
    Assert::equals(
      "T@[\n  id => 1\n  name => \"Test\"\n]",
      $t->method('toString')->invoke($t->newInstance(1, 'Test'))
    );
  }

  #[Test, Values([['id', 1], ['name', 'Test']])]
  public function generates_accessor($name, $expected) {
    $t= $this->declare('#[Getters] class %T {
      private int $id;
      private string $name;

      public function __construct(int $id, string $name) {
        $this->id= $id;
        $this->name= $name;
      }
    }');
    Assert::notEquals(null, $t->method($name));
    Assert::equals($expected, $t->method($name)->invoke($t->newInstance(1, 'Test')));
  }

  #[Test]
  public function generates_both() {
    $t= $this->declare('#[Repr, Getters] class %T {
      private int $id;
      private string $name;

      public function __construct(int $id, string $name) {
        $this->id= $id;
        $this->name= $name;
      }
    }');

    $instance= $t->newInstance(1, 'Test');
    Assert::equals(1, $t->method('id')->invoke($instance));
    Assert::equals('Test', $t->method('name')->invoke($instance));
    Assert::equals(
      "T@[\n  id => 1\n  name => \"Test\"\n]",
      $t->method('toString')->invoke($instance)
    );
  }
}