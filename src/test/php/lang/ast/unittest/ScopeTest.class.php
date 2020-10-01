<?php namespace lang\ast\unittest;

use lang\ast\Scope;
use unittest\{Assert, Test};

class ScopeTest {

  #[Test]
  public function can_create() {
    new Scope();
  }

  #[Test]
  public function package() {
    $s= new Scope();
    $s->package('test');

    Assert::equals('\\test', $s->package);
  }

  #[Test]
  public function resolve_in_global_scope() {
    $s= new Scope();

    Assert::equals('\\Parse', $s->resolve('Parse'));
  }

  #[Test]
  public function resolve_in_package() {
    $s= new Scope();
    $s->package('test');

    Assert::equals('\\test\\Parse', $s->resolve('Parse'));
  }

  #[Test]
  public function resolve_relative_in_package() {
    $s= new Scope();
    $s->package('test');

    Assert::equals('\\test\\ast\\Parse', $s->resolve('ast\\Parse'));
  }

  #[Test]
  public function resolve_imported_in_package() {
    $s= new Scope();
    $s->package('test');
    $s->import('lang\\ast\\Parse');

    Assert::equals('\\lang\\ast\\Parse', $s->resolve('Parse'));
  }

  #[Test]
  public function resolve_imported_in_global_scope() {
    $s= new Scope();
    $s->import('lang\\ast\\Parse');

    Assert::equals('\\lang\\ast\\Parse', $s->resolve('Parse'));
  }

  #[Test]
  public function package_inherited_from_parent() {
    $s= new Scope();
    $s->package('test');

    Assert::equals('\\test\\Parse', (new Scope($s))->resolve('Parse'));
  }

  #[Test]
  public function import_inherited_from_parent() {
    $s= new Scope();
    $s->import('lang\\ast\\Parse');

    Assert::equals('\\lang\\ast\\Parse', (new Scope($s))->resolve('Parse'));
  }
}