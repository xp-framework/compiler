<?php namespace lang\ast\unittest\emit;

use lang\ast\Code;
use lang\ast\nodes\Method;
use lang\ast\nodes\Signature;

class TransformationsTest extends EmittingTest {

  /** @return void */
  public function setUp() {
    $this->transform('class', function($class) {
      if ($class->annotation('getters')) {
        foreach ($class->properties() as $property) {
          $class->inject(new Method(
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

  /** @return void */
  public function tearDown() {
    $this->transform('class', null);
  }

  #[@test, @values([['id', 1], ['name', 'Test']])]
  public function generates_accessor($name, $expected) {
    $t= $this->type('<<getters>> class <T> {
      private int $id;
      private string $name;

      public function __construct(int $id, string $name) {
        $this->id= $id;
        $this->name= $name;
      }
    }');
    $this->assertTrue($t->hasMethod($name));
    $this->assertEquals($expected, $t->getMethod($name)->invoke($t->newInstance(1, 'Test')));
  }
}