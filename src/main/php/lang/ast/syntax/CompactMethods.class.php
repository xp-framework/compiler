<?php namespace lang\ast\syntax;

use lang\ast\Node;
use lang\ast\nodes\Method;

class CompactMethods {

  public function setup($parser, $emitter) {
    $parser->body('fn', function($parse, &$body, $annotations, $modifiers) {
      $member= new Node($parse->token->symbol);
      $member->kind= 'method';
      $member->line= $parse->token->line;
      $comment= $parse->comment;
      $parse->comment= null;

      $parse->forward();
      $name= $parse->token->value;
      $lookup= $name.'()';
      if (isset($body[$lookup])) {
        $parse->raise('Cannot redeclare method '.$lookup);
      }

      $parse->forward();
      $signature= $this->signature($parse);

      $parse->expecting('=>', 'compact function');
      $return= new Node($parse->token->symbol);
      $return->line= $parse->token->line;
      $return->value= $this->expressionWithThrows($parse, 0);
      $return->kind= 'return';
      $parse->expecting(';', 'compact function');

      $member->value= new Method($modifiers, $name, $signature, [$return], $annotations, $comment);
      $body[$lookup]= $member;
    });
  }
}