<?php namespace lang\ast;

class Emitter {
  private $out;

  public function __construct($out) {
    $this->out= $out;
  }

  private function param($param) {
    if ($param[2]) {
      return $param[1].' ... $'.$param[0];  
    } else {
      return $param[1].' $'.$param[0];
    }
  }

  private function params($params) {
    $r= '';
    foreach ($params as $param) {
      $r.= ', '.$this->param($param);
    }
    $this->out->write(substr($r, 2));
  }

  private function arguments($list) {
    $s= sizeof($list) - 1;
    foreach ($list as $i => $argument) {
      $this->emit($argument);
      if ($i < $s) $this->out->write(', ');
    }
  }

  private function annotations($list) {
    $s= sizeof($list) - 1;
    $this->out->write('#[');
    foreach ($list as $i => $annotation) {
      $this->out->write('@'.$annotation[0]);
      if (isset($annotation[1])) {
        $this->out->write('(');
        $this->arguments($annotation[1]);
        $this->out->write(')');
      }
      if ($i < $s) $this->out->write(', ');
    }
    $this->out->write("]\n");
  }

  private function start($node) {
    $this->out->write('<?php ');
  }

  private function package($node) {
    $this->out->write('namespace '.$node->value.";\n");
  }

  private function import($node) {
    $this->out->write('');
  }

  private function literal($node) {
    $this->out->write(var_export($node->value, true));
  }

  private function name($node) {
    $this->out->write($node->value);
  }

  private function variable($node) {
    $this->out->write('$'.$node->value);
  }

  private function function($node) {
    $this->out->write('function '.$node->value[0].'('); 
    $this->params($node->value[2]);
    $this->out->write(') {');
    $this->emit($node->value[3]);
    $this->out->write('}');
  }

  private function closure($node) {
    $this->out->write('function('); 
    $this->params($node->value[2]);
    $this->out->write(') {');
    $this->emit($node->value[3]);
    $this->out->write('}');
  }

  private function class($node) {
    $this->out->write('class '.$node->value[0]);
    $node->value[1] && $this->out->write(' extends '.$node->value[1]);
    $node->value[2] && $this->out->write(' implements '.implode(', ', $node->value[2]));
    $this->out->write('{');
    foreach ($node->value[3] as $member) {
      $this->emit($member);
      $this->out->write("\n");
    }
    $this->out->write('}');
  }

  private function interface($node) {
    $this->out->write('interface '.$node->value[0]);
    $node->value[1] && $this->out->write(' extends '.implode(', ', $node->value[1]));
    $this->out->write('{');
    foreach ($node->value[2] as $member) {
      $this->emit($member);
      $this->out->write("\n");
    }
    $this->out->write('}');
  }

  private function trait($node) {
    $this->out->write('trait '.$node->value[0]);
    $this->out->write('{');
    foreach ($node->value[1] as $member) {
      $this->emit($member);
      $this->out->write("\n");
    }
    $this->out->write('}');
  }

  private function property($node) {
    if (isset($node->value[3])) {
      $this->out->write("\n/** @var ".$node->value[3]." */\n");
    }
    if (isset($node->value[4])) {
      $this->out->write("\n");
      $this->annotations($node->value[4]);
    }
    $this->out->write(implode(' ', $node->value[1]).' $'.$node->value[0]);
    if (isset($node->value[2])) {
      $this->out->write('=');
      $this->emit($node->value[2]);
    }
    $this->out->write(';');
  }

  private function method($node) {
    $declare= $promote= $params= '';
    foreach ($node->value[2] as $param) {
      if (isset($param[3])) {
        $declare= $param[3].' $'.$param[0].';';
        $promote.= '$this->'.$param[0].'= $'.$param[0].';';
      }
      $params.= ', '.$this->param($param);
    }
    $this->out->write($declare);
    if (isset($node->value[5])) {
      $this->out->write("\n");
      $this->annotations($node->value[5]);
    }
    $this->out->write(implode(' ', $node->value[1]).' function '.$node->value[0].'('.substr($params, 2).')');
    if (isset($node->value[4])) {
      $this->out->write(':'.$node->value[4]);
    }
    if (null === $node->value[3]) {
      $this->out->write(';');
    } else {
      $this->out->write(' {'.$promote);
      $this->emit($node->value[3]);
      $this->out->write('}');
    }
  }

  private function binary($node) {
    $this->emit($node->value[0]);
    $this->out->write($node->symbol->id);
    $this->emit($node->value[1]);
  }

  private function ternary($node) {
    $this->emit($node->value[0]);
    $this->out->write('?');
    $this->emit($node->value[1]);
    $this->out->write(':');
    $this->emit($node->value[1]);
  }

  private function offset($node) {
    $this->emit($node->value[0]);
    $this->out->write('[');
    $this->emit($node->value[1]);
    $this->out->write(']');
  }

  private function assignment($node) {
    $this->emit($node->value[0]);
    $this->out->write('=');
    $this->emit($node->value[1]);
  }

  private function return($node) {
    $this->out->write('return ');
    $this->emit($node->value);
    $this->out->write(';');
  }

  private function try($node) {
    $this->out->write('try {');
    $this->emit($node->value[0]);
    $this->out->write('}');
    if (isset($node->value[1])) {
      foreach ($node->value[1] as $catch) {

        // TODO: Refactor into subclasses!
        if (PHP_VERSION_ID >= 70100) {
          $this->out->write('catch('.implode('|', $catch[0]).' $'.$catch[1].') {');
          $this->emit($catch[2]);
          $this->out->write('}');
        } else {
          $last= array_pop($catch[0]);
          $label= 'c'.crc32($last);
          foreach ($catch[0] as $type) {
            $this->out->write('catch('.$type.' $'.$catch[1].') { goto '.$label.'; }');
          }
          $this->out->write('catch('.$last.' $'.$catch[1].') { '.$label.':');
          $this->emit($catch[2]);
          $this->out->write('}');
        }
      }
    }
    if (isset($node->value[2])) {
      $this->out->write('finally {');
      $this->emit($node->value[2]);
      $this->out->write('}');
    }
  }

  private function throw($node) {
    $this->out->write('throw ');
    $this->emit($node->value);
  }

  private function new($node) {
    $this->out->write('new '.$node->value[0].'(');
    $this->arguments($node->value[1]);
    $this->out->write(')');
  }

  private function invoke($node) {
    $this->emit($node->value[0]);
    $this->out->write('(');
    $this->arguments($node->value[1]);
    $this->out->write(')');
  }

  private function static($node) {
    $this->out->write($node->value[0].'::');
    $this->emit($node->value[1]);
  }

  private function instance($node) {
    if ('new' === $node->value[0]->arity) {
      $this->out->write('(');
      $this->emit($node->value[0]);
      $this->out->write(')->');
    } else {
      $this->emit($node->value[0]);
      $this->out->write('->');
    }
    $this->emit($node->value[1]);
  }

  private function unpack($node) {
    $this->out->write('...');
    $this->emit($node->value);
  }

  public function emit($arg) {
    if ($arg instanceof Node) {
      $this->{$arg->arity}($arg);
    } else {
      foreach ($arg as $node) {
        $this->{$node->arity}($node);
        isset($node->symbol->std) || $this->out->write(";\n"); 
      }
    }
  }
}