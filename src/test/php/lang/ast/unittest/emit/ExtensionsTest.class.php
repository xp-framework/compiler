<?php namespace lang\ast\unittest\emit;

use lang\ast\nodes\{Literal, BinaryExpression, InvokeExpression};
use test\{Assert, Before, Test, Values};

class ExtensionsTest extends EmittingTest {

  #[Before]
  public function install() {

    // See https://learn.microsoft.com/en-us/dotnet/csharp/language-reference/keywords/extension,
    // this is what we might parse this from as an idea:
    //
    // ```php
    // abstract class StringExtensions {
    //   extension(string) {
    //     public function length(string $self) => strlen($self);
    //     public function contains(string $self, string $needle) => false !== strpos($self, $needle);
    //   }
    // }
    // ```
    // 
    // Single-expression methods would be inlined at call-site!
    $this->extension('string', [
      'length'   => function($self, $arguments) {
        return new InvokeExpression(new Literal('strlen'), [$self]);
      },
      'contains' => function($self, $arguments) {
        return new BinaryExpression(
          new Literal('false'),
          '!==',
          new InvokeExpression(new Literal('strpos'), [$self, $arguments[0]])
        );
      }
    ]);
  }

  #[Test]
  public function literal() {
    Assert::equals(4, $this->run('class %T {
      public function run() {
        return "Test"->length();
      }
    }'));
  }

  #[Test]
  public function local() {
    Assert::equals(4, $this->run('class %T {
      public function run() {
        $test= "Test";
        return $test->length();
      }
    }'));
  }

  #[Test, Values([['', 0], ['Test', 4]])]
  public function param_length($input, $expected) {
    Assert::equals($expected, $this->run('class %T {
      public function run(string $input) {
        return $input->length();
      }
    }', $input));
  }

  #[Test, Values([['', false], ['Test', true]])]
  public function param_contains($input, $expected) {
    Assert::equals($expected, $this->run('class %T {
      public function run(string $input) {
        return $input->contains("Test");
      }
    }', $input));
  }
}