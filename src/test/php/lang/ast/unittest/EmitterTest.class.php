<?php namespace lang\ast\unittest;

use io\streams\MemoryOutputStream;
use lang\ast\nodes\{Variable, Comment};
use lang\ast\{Emitter, Node, Code, Result};
use lang\{IllegalStateException, IllegalArgumentException};
use unittest\{Assert, Expect, Test, TestCase};

class EmitterTest {

  private function newEmitter() {
    return Emitter::forRuntime('php:'.PHP_VERSION)->newInstance();
  }

  #[Test]
  public function can_create() {
    $this->newEmitter();
  }

  #[Test]
  public function dotted_argument_bc() {
    Assert::equals(Emitter::forRuntime('php:'.PHP_VERSION), Emitter::forRuntime('PHP.'.PHP_VERSION));
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function cannot_create_for_unsupported_php_version() {
    Emitter::forRuntime('php:4.3.0');
  }

  #[Test]
  public function transformations_initially_empty() {
    Assert::equals([], $this->newEmitter()->transformations());
  }

  #[Test]
  public function transform() {
    $function= function($class) { return $class; };

    $fixture= $this->newEmitter();
    $fixture->transform('class', $function);
    Assert::equals(['class' => [$function]], $fixture->transformations());
  }

  #[Test]
  public function remove() {
    $first= function($codegen, $class) { return $class; };
    $second= function($codegen, $class) { $class->annotations['author']= 'Test'; return $class; };

    $fixture= $this->newEmitter();
    $transformation= $fixture->transform('class', $first);
    $fixture->transform('class', $second);
    $fixture->remove($transformation);
    Assert::equals(['class' => [$second]], $fixture->transformations());
  }

  #[Test]
  public function remove_unsets_empty_kind() {
    $function= function($codegen, $class) { return $class; };

    $fixture= $this->newEmitter();
    $transformation= $fixture->transform('class', $function);
    $fixture->remove($transformation);
    Assert::equals([], $fixture->transformations());
  }

  #[Test, Expect(IllegalStateException::class)]
  public function emit_node_without_kind() {
    $node= new class() extends Node {
      public $kind= null;
    };
    $this->newEmitter()->write([$node], new MemoryOutputStream());
  }

  #[Test]
  public function transform_modifying_node() {
    $fixture= $this->newEmitter();
    $fixture->transform('variable', function($codegen, $var) { $var->name= '_'.$var->name; return $var; });
    $out= $fixture->write([new Variable('a')], new MemoryOutputStream());

    Assert::equals('<?php $_a;', $out->bytes());
  }

  #[Test]
  public function transform_to_node() {
    $fixture= $this->newEmitter();
    $fixture->transform('variable', function($codegen, $var) { return new Code('$variables["'.$var->name.'"]'); });
    $out= $fixture->write([new Variable('a')], new MemoryOutputStream());

    Assert::equals('<?php $variables["a"];', $out->bytes());
  }

  #[Test]
  public function transform_to_array() {
    $fixture= $this->newEmitter();
    $fixture->transform('variable', function($codegen, $var) { return [new Code('$variables["'.$var->name.'"]')]; });
    $out= $fixture->write([new Variable('a')], new MemoryOutputStream());

    Assert::equals('<?php $variables["a"];;', $out->bytes());
  }

  #[Test]
  public function transform_to_null() {
    $fixture= $this->newEmitter();
    $fixture->transform('variable', function($codegen, $var) { return null; });
    $out= $fixture->write([new Variable('a')], new MemoryOutputStream());

    Assert::equals('<?php $a;', $out->bytes());
  }

  #[Test]
  public function emit_multiline_comment() {
    $out= $this->newEmitter()->write(
      [
        new Comment(
          "/**\n".
          " * Doc comment\n".
          " *\n".
          " * @see http://example.com/\n".
          " */",
          3
        ),
        new Variable('a', 8)
      ],
      new MemoryOutputStream()
    );

    $code= $out->bytes();
    Assert::equals('$a;', explode("\n", $code)[7], $code);
  }
}