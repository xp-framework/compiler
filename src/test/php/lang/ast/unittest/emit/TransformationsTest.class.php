<?php namespace lang\ast\unittest\emit;

use lang\ast\Code;
use lang\ast\nodes\Method;
use lang\ast\nodes\Signature;
use lang\ast\transform\Transformations;

class TransformationsTest extends EmittingTest {

  #[@beforeClass]
  public static function registerTransformation() {
    Transformations::register('class', function($class) {
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

  #[@test]
  public function generates_accessor() {
    $t= $this->type('<<getters>> class <T> {
      private int $id;
      private string $name;

      public function __construct(int $id, string $name) {
        $this->id= $id;
        $this->name= $name;
      }
    }');
    $this->assertTrue($t->hasMethod('id'));
  }
}