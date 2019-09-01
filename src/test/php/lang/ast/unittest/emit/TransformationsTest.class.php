<?php namespace lang\ast\unittest\emit;

use lang\ast\Code;
use lang\ast\nodes\Method;
use lang\ast\nodes\Signature;

class TransformationsTest extends EmittingTest {

  #[@beforeClass]
  public static function registerTransformation() {
    self::transform('class', function($class) {
      if ($class->value->annotation('getters')) {
        foreach ($class->value->properties() as $property) {
          $class->value->inject(new Method(
            ['public'],
            $property->name,
            new Signature([], $property->type),
            [new Code('return $this->'.$property->name.';')]
          ));
        }
      }
      yield $class;
    });
  }

  #[@afterClass]
  public static function removeTransformation() {
    self::transform('class', null);
  }

  #[@test, @values(['id', 'name'])]
  public function generates_accessor($name) {
    $t= $this->type('<<getters>> class <T> {
      private int $id;
      private string $name;

      public function __construct(int $id, string $name) {
        $this->id= $id;
        $this->name= $name;
      }
    }');
    $this->assertTrue($t->hasMethod($name));
  }
}