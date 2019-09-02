<?php namespace lang\ast\syntax;

use lang\ast\Node;
use lang\ast\nodes\Method;
use lang\ast\nodes\ReturnStatement;

class CompactMethods {

  public function setup($parser, $emitter) {
    $parser->body('fn', function($parse, &$body, $annotations, $modifiers) {
      $line= $parse->token->line;
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
      $return= new ReturnStatement($this->expressionWithThrows($parse, 0), $parse->token->line);
      $parse->expecting(';', 'compact function');

      $body[$lookup]= new Method($modifiers, $name, $signature, [$return], $annotations, $comment, $line);
    });
  }
}