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

    $emitter->handle('using', function($node) {
      $variables= [];
      foreach ($node->value->arguments as $expression) {
        switch ($expression->kind) {
          case 'variable': $variables[]= '$'.$expression->value; break;
          case 'assignment': $variables[]= '$'.$expression->value->variable->value; break;
          default: $temp= $this->temp(); $variables[]= $temp; $this->out->write($temp.'=');
        }
        $this->emit($expression);
        $this->out->write(';');
      }

      $this->out->write('try {');
      $this->emit($node->value->body);

      $this->out->write('} finally {');
      foreach ($variables as $variable) {
        $this->out->write('if ('.$variable.' instanceof \lang\Closeable) { '.$variable.'->close(); }');
        $this->out->write('else if ('.$variable.' instanceof \IDisposable) { '.$variable.'->__dispose(); }');
        $this->out->write('unset('.$variable.');');
      }
      $this->out->write('}');
    });
  }
}