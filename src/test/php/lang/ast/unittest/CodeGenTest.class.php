<?php namespace lang\ast\unittest;

use lang\Value;
use lang\ast\CodeGen;
use lang\ast\emit\{Declaration, InType, Reflection, Incomplete};
use lang\ast\nodes\ClassDeclaration;
use lang\ast\types\IsValue;
use test\{Assert, Test};

class CodeGenTest {

  #[Test]
  public function can_create() {
    new CodeGen();
  }


  #[Test]
  public function lookup_self() {
    $fixture= new CodeGen();
    $context= $fixture->enter(new InType(new ClassDeclaration([], new IsValue('\\T'), null, [], [], null, null, 1)));

    Assert::equals(new Declaration($context->type, $fixture), $fixture->lookup('self'));
  }

  #[Test]
  public function lookup_parent() {
    $fixture= new CodeGen();
    $fixture->enter(new InType(new ClassDeclaration([], new IsValue('\\T'), new IsValue('\\lang\\Value'), [], [], null, null, 1)));

    Assert::equals(new Reflection(Value::class), $fixture->lookup('parent'));
  }

  #[Test]
  public function lookup_parent_without_parent() {
    $fixture= new CodeGen();
    $fixture->enter(new InType(new ClassDeclaration([], new IsValue('\\T'), null, [], [], null, null, 1)));

    Assert::null($fixture->lookup('parent'));
  }

  #[Test]
  public function lookup_named() {
    $fixture= new CodeGen();
    $context= $fixture->enter(new InType(new ClassDeclaration([], new IsValue('\\T'), null, [], [], null, null, 1)));

    Assert::equals(new Declaration($context->type, $fixture), $fixture->lookup('\\T'));
  }

  #[Test]
  public function lookup_value_interface() {
    $fixture= new CodeGen();

    Assert::equals(new Reflection(Value::class), $fixture->lookup('\\lang\\Value'));
  }

  #[Test]
  public function lookup_non_existant() {
    $fixture= new CodeGen();
    Assert::instance(Incomplete::class, $fixture->lookup('\\NotFound'));
  }
}