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

      return new UsingStatement($arguments, $statements);
    });

    $emitter->handle('using', function($result, $node) {
      $variables= [];
      foreach ($node->arguments as $expression) {
        switch ($expression->kind) {
          case 'variable': $variables[]= '$'.$expression->name; break;
          case 'assignment': $variables[]= '$'.$expression->variable->name; break;
          default: $temp= $result->temp(); $variables[]= $temp; $result->out->write($temp.'=');
        }
        $this->emit($result, $expression);
        $result->out->write(';');
      }

      $result->out->write('try {');
      $this->emitAll($result, $node->body);

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