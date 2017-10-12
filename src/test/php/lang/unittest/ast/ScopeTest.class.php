<?php namespace lang\unittest\ast;

use lang\ast\Scope;

class ScopeTest extends \unittest\TestCase {

  #[@test]
  public function can_create() {
    new Scope();
  }

  #[@test]
  public function package() {
    $s= new Scope();
    $s->package('test');

    $this->assertEquals('\\test', $s->package);
  }

  #[@test]
  public function resolve_in_global_scope() {
    $s= new Scope();

    $this->assertEquals('Parse', $s->resolve('Parse'));
  }

  #[@test]
  public function resolve_in_package() {
    $s= new Scope();
    $s->package('test');

    $this->assertEquals('\\test\\Parse', $s->resolve('Parse'));
  }

  #[@test]
  public function resolve_imported_in_package() {
    $s= new Scope();
    $s->package('test');
    $s->import('lang\\ast\\Parse');

    $this->assertEquals('\\lang\\ast\\Parse', $s->resolve('Parse'));
  }

  #[@test]
  public function resolve_imported_in_global_scope() {
    $s= new Scope();
    $s->import('lang\\ast\\Parse');

    $this->assertEquals('\\lang\\ast\\Parse', $s->resolve('Parse'));
  }

  #[@test]
  public function package_inherited_from_parent() {
    $s= new Scope();
    $s->package('test');

    $this->assertEquals('\\test\\Parse', (new Scope($s))->resolve('Parse'));
  }

  #[@test]
  public function import_inherited_from_parent() {
    $s= new Scope();
    $s->import('lang\\ast\\Parse');

    $this->assertEquals('\\lang\\ast\\Parse', (new Scope($s))->resolve('Parse'));
  }
}