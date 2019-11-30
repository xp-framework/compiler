<?php namespace lang\ast\unittest\parse;

use lang\ast\nodes\{NamespaceDeclaration, UseStatement};
use unittest\Assert;

class NamespacesTest extends ParseTest {

  #[@test]
  public function simple_namespace() {
    $this->assertParsed(
      [new NamespaceDeclaration('test', self::LINE)],
      'namespace test;'
    );
  }

  #[@test]
  public function compound_namespace() {
    $this->assertParsed(
      [new NamespaceDeclaration('lang\\ast', self::LINE)],
      'namespace lang\\ast;'
    );
  }

  #[@test]
  public function use_statement() {
    $this->assertParsed(
      [new UseStatement(null, ['lang\ast\Parse' => null], self::LINE)],
      'use lang\\ast\\Parse;'
    );
  }

  #[@test]
  public function use_with_alias() {
    $this->assertParsed(
      [new UseStatement(null, ['lang\ast\Parse' => 'P'], self::LINE)],
      'use lang\\ast\\Parse as P;'
    );
  }

  #[@test]
  public function grouped_use_statement() {
    $this->assertParsed(
      [new UseStatement(null, ['lang\\ast\\Parse' => null, 'lang\\ast\\Emitter' => null], self::LINE)],
      'use lang\\ast\\{Parse, Emitter};'
    );
  }

  #[@test]
  public function grouped_use_with_alias() {
    $this->assertParsed(
      [new UseStatement(null, ['lang\\ast\\Parse' => 'P'], self::LINE)],
      'use lang\\ast\\{Parse as P};'
    );
  }
}