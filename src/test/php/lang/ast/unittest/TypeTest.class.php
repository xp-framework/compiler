<?php namespace lang\ast\unittest;

use lang\ast\Type;

class TypeTest extends \unittest\TestCase {

  #[@test]
  public function can_create() {
    new Type('string');
  }

  #[@test, @values([
  #  'string',
  #  '?string',
  #  'array',
  #  'array<int>',
  #  'array<string, string>',
  #  'Value',
  #  '\\lang\\Value',
  #  '?\\lang\\Value',
  #])]
  public function literal($literal) {
    $this->assertEquals($literal, (new Type($literal))->literal());
  }

  #[@test, @values([
  #  ['string', 'string'],
  #  ['?string', '?string'],
  #  ['array', 'array'],
  #  ['array<int>', 'array<int>'],
  #  ['array<string, string>', 'array<string, string>'],
  #  ['Value', 'Value'],
  #  ['\\lang\\Value', 'lang.Value'],
  #  ['?\\lang\\Value', '?lang.Value'],
  #])]
  public function name($literal, $name) {
    $this->assertEquals($name, (new Type($literal))->name());
  }
}