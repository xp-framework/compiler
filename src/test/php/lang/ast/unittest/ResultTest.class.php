<?php namespace lang\ast\unittest;

use io\streams\{StringWriter, MemoryOutputStream};
use lang\Value;
use lang\ast\Result;
use lang\ast\emit\{Declaration, Reflection};
use lang\ast\nodes\ClassDeclaration;
use unittest\{Assert, Test};

class ResultTest {

  #[Test]
  public function can_create() {
    new Result(new StringWriter(new MemoryOutputStream()));
  }

  #[Test]
  public function creates_unique_temporary_variables() {
    $r= new Result(new StringWriter(new MemoryOutputStream()));
    Assert::equals(['$_0', '$_1', '$_2'], [$r->temp(), $r->temp(), $r->temp()]);
  }

  #[Test]
  public function writes_php_open_tag_as_default_preamble() {
    $out= new MemoryOutputStream();
    $r= new Result(new StringWriter($out));
    Assert::equals('<?php ', $out->bytes());
  }

  #[Test, Values(['', '<?php '])]
  public function writes_preamble($preamble) {
    $out= new MemoryOutputStream();
    $r= new Result(new StringWriter($out), $preamble);
    Assert::equals($preamble, $out->bytes());
  }

  #[Test]
  public function write() {
    $out= new MemoryOutputStream();
    $r= new Result(new StringWriter($out));
    $r->out->write('echo "Hello";');
    Assert::equals('<?php echo "Hello";', $out->bytes());
  }

  #[Test]
  public function lookup_self() {
    $r= new Result(new StringWriter(new MemoryOutputStream()));
    $r->type[0]= new ClassDeclaration([], '\\T', null, [], [], [], null, 1);

    Assert::equals(new Declaration($r->type[0], $r), $r->lookup('self'));
  }

  #[Test]
  public function lookup_parent() {
    $r= new Result(new StringWriter(new MemoryOutputStream()));
    $r->type[0]= new ClassDeclaration([], '\\T', '\\lang\\Value', [], [], [], null, 1);

    Assert::equals(new Reflection(Value::class), $r->lookup('parent'));
  }

  #[Test]
  public function lookup_named() {
    $r= new Result(new StringWriter(new MemoryOutputStream()));
    $r->type[0]= new ClassDeclaration([], '\\T', null, [], [], [], null, 1);

    Assert::equals(new Declaration($r->type[0], $r), $r->lookup('\\T'));
  }

  #[Test]
  public function lookup_value_interface() {
    $r= new Result(new StringWriter(new MemoryOutputStream()));

    Assert::equals(new Reflection(Value::class), $r->lookup('\\lang\\Value'));
  }
}