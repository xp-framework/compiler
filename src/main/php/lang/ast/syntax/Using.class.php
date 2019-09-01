<?php namespace lang\ast\syntax;

use lang\ast\nodes\UsingStatement;

class Using {

  public function setup($language, $emitter) {
    $language->stmt('using', function($parse, $node) {
      $parse->expecting('(', 'using arguments');
      $arguments= $this->expressions($parse, ')');
      $parse->expecting(')', 'using arguments');

      $parse->expecting('{', 'using block');
      $statements= $this->statements($parse);
      $parse->expecting('}', 'using block');

      $node->value= new UsingStatement($arguments, $statements);
      $node->kind= 'using';
      return $node;
    });

    $emitter->handle('using', function($result, $node) {
      $variables= [];
      foreach ($node->value->arguments as $expression) {
        switch ($expression->kind) {
          case 'variable': $variables[]= '$'.$expression->value; break;
          case 'assignment': $variables[]= '$'.$expression->value->variable->value; break;
          default: $temp= $result->temp(); $variables[]= $temp; $result->out->write($temp.'=');
        }
        $this->emit($result, $expression);
        $result->out->write(';');
      }

      $result->out->write('try {');
      $this->emit($result, $node->value->body);

      $result->out->write('} finally {');
      foreach ($variables as $variable) {
        $result->out->write('if ('.$variable.' instanceof \lang\Closeable) { '.$variable.'->close(); }');
        $result->out->write('else if ('.$variable.' instanceof \IDisposable) { '.$variable.'->__dispose(); }');
        $result->out->write('unset('.$variable.');');
      }
      $result->out->write('}');
    });
  }
}