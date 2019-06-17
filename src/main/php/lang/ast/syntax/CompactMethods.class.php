<?php namespace lang\ast\syntax;

use lang\ast\Node;
use lang\ast\nodes\Method;

class CompactMethods {

  public function setup($parser) {
    $parser->body('fn', function(&$body, $annotations, $modifiers) {
      $member= new Node($this->token->symbol);
      $member->kind= 'method';
      $member->line= $this->token->line;
      $comment= $this->comment;
      $this->comment= null;

      $this->token= $this->advance();
      $name= $this->token->value;
      $lookup= $name.'()';
      if (isset($body[$lookup])) {
        $this->raise('Cannot redeclare method '.$lookup);
      }

      $this->token= $this->advance();
      $signature= $this->signature();

      $this->token= $this->expect('=>');
      $return= new Node($this->token->symbol);
      $return->line= $this->token->line;
      $return->value= $this->expressionWithThrows(0);
      $return->kind= 'return';
      $this->token= $this->expect(';');

      $member->value= new Method($modifiers, $name, $signature, [$return], $annotations, $comment);
      $body[$lookup]= $member;
    });
  }
}