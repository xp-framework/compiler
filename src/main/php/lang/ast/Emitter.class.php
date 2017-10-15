<?php namespace lang\ast;

use lang\reflect\Package;
use lang\IllegalArgumentException;
use io\streams\MemoryOutputStream;
use io\streams\StringWriter;

abstract class Emitter {
  const PROPERTY = 0;
  const METHOD   = 1;

  protected $out;
  protected $meta= [];

  /**
   * Selects the correct emitter for a given runtime version
   *
   * @param  string $version E.g. PHP_VERSION
   * @return self
   * @throws lang.IllegalArgumentException
   */
  public static function forRuntime($version) {
    sscanf($version, '%d.%d', $major, $minor);
    $p= Package::forName('lang.ast.emit');

    do {
      $impl= 'PHP'.$major.$minor;
      if ($p->providesClass($impl)) return $p->loadClass($impl);
    } while ($minor-- > 0);

    throw new IllegalArgumentException('XP Compiler does not support PHP '.$version.' yet');
  }

  /** @param io.streams.Writer */
  public function __construct($out) {
    $this->out= $out;
    $this->id= 0;
  }

  /**
   * Creates a temporary variable and returns its name
   *
   * @param  string
   */
  protected function temp() {
    return '$T'.($this->id++);
  }

  /**
   * Collects emitted code into a buffer and returns it
   *
   * @param  function(): void $callable
   * @return string
   */
  protected function buffer($callable) {
    $o= $this->out;
    $buffer= new MemoryOutputStream();
    $this->out= new StringWriter($buffer  );

    try {
      $callable();
      return $buffer->getBytes();
    } finally {
      $this->out= $o;
    }
  }

  /**
   * Returns the simple name for use in a declaration
   *
   * @param  string $name E.g. `\lang\ast\Parse`
   * @return string In the above example, `Parse`.
   */
  protected function declaration($name) {
    return substr($name, strrpos($name, '\\') + 1);
  }

  protected abstract function type($name);

  protected function catches($catch) {
    $this->out->write('catch('.implode('|', $catch[0]).' $'.$catch[1].') {');
    $this->emit($catch[2]);
    $this->out->write('}');
  }

  protected function param($param) {
    $this->out->write($this->type($param[2]));
    if ($param[3]) {
      $this->out->write('... $'.$param[0]);
    } else {
      $this->out->write(' '.($param[1] ? '&' : '').'$'.$param[0]);
    }
    if ($param[5]) {
      $this->out->write('=');
      $this->emit($param[5]);
    }
  }

  protected function params($params) {
    $s= sizeof($params) - 1;
    foreach ($params as $i => $param) {
      $this->param($param);
      if ($i < $s) $this->out->write(', ');
    }
  }

  protected function arguments($list) {
    $s= sizeof($list) - 1;
    foreach ($list as $i => $argument) {
      $this->emit($argument);
      if ($i < $s) $this->out->write(', ');
    }
  }

  private function annotations($list) {
    foreach ($list as $annotation) {
      $this->out->write("'".$annotation[0]."' => ");
      if (isset($annotation[1])) {
        $this->emit($annotation[1]);
        $this->out->write(',');
      } else {
        $this->out->write('null,');
      }
    }
  }

  protected function emitStart($node) {
    $this->out->write('<?php ');
  }

  protected function emitPackage($node) {
    $this->out->write('namespace '.$node->value.";\n");
  }

  protected function emitImport($node) {
    // NOOP
  }

  protected function emitAnnotation($node) {
    // NOOP
  }

  protected function emitLiteral($node) {
    $this->out->write($node->value);
  }

  protected function emitName($node) {
    $this->out->write($node->value);
  }

  protected function emitBlock($node) {
    $this->out->write('{');
    $this->emit($node->value);
    $this->out->write('}');
  }

  protected function emitStatic($node) {
    foreach ($node->value as $variable => $initial) {
      $this->out->write('static $'.$variable);
      if ($initial) {
        $this->out->write('=');
        $this->emit($initial);
      }
      $this->out->write(';');
    }
  }

  protected function emitVariable($node) {
    $this->out->write('$'.$node->value);
  }

  protected function emitCast($node) {
    $this->out->write('('.$node->value[0].')');
    $this->emit($node->value[1]);
  }

  protected function emitArray($node) {
    if (empty($node->value)) {
      $this->out->write('[]');
      return;
    }

    $this->out->write('[');
    foreach ($node->value as $key => $value) {
      $this->out->write($key.'=>');
      $this->emit($value);
      $this->out->write(',');
    }
    $this->out->write(']');
  }

  protected function emitFunction($node) {
    $this->out->write('function '.$node->value[0].'('); 
    $this->params($node->value[2]);
    $this->out->write(') {');
    $this->emit($node->value[3]);
    $this->out->write('}');
  }

  protected function emitClosure($node) {
    $this->out->write('function('); 
    $this->params($node->value[2]);
    $this->out->write(') ');
    if (isset($node->value[5])) {
      $this->out->write('use('.implode(',', $node->value[5]).') ');
    }
    $this->out->write('{');
    $this->emit($node->value[3]);
    $this->out->write('}');
  }

  protected function emitClass($node) {
    array_unshift($this->meta, []);
    $this->out->write(implode(' ', $node->value[1]).' class '.$this->declaration($node->value[0]));
    $node->value[2] && $this->out->write(' extends '.$node->value[2]);
    $node->value[3] && $this->out->write(' implements '.implode(', ', $node->value[3]));
    $this->out->write('{');
    foreach ($node->value[4] as $member) {
      $this->emit($member);
      $this->out->write("\n");
    }
    $this->out->write('}');

    // Cache annotations
    $this->out->write('\xp::$meta[\''.substr(str_replace('\\', '.', $node->value[0]), 1).'\']= [');
    $this->out->write('"class" => [DETAIL_ANNOTATIONS => [');
    $this->annotations($node->value[5]);
    $this->out->write(']],');

    foreach (array_shift($this->meta) as $type => $lookup) {
      $this->out->write($type.' => [');
      foreach ($lookup as $key => $annotations) {
        $this->out->write("'".$key."' => [DETAIL_ANNOTATIONS => [");
        $this->annotations($annotations);
        $this->out->write(']],');
      }
      $this->out->write('],');
    }
    $this->out->write('];');
  }

  protected function emitInterface($node) {
    $this->out->write('interface '.$this->declaration($node->value[0]));
    $node->value[2] && $this->out->write(' extends '.implode(', ', $node->value[2]));
    $this->out->write('{');
    foreach ($node->value[3] as $member) {
      $this->emit($member);
      $this->out->write("\n");
    }
    $this->out->write('}');
  }

  protected function emitTrait($node) {
    $this->out->write('trait '.$this->declaration($node->value[0]));
    $this->out->write('{');
    foreach ($node->value[2] as $member) {
      $this->emit($member);
      $this->out->write("\n");
    }
    $this->out->write('}');
  }

  protected function emitConst($node) {
    $this->out->write(implode(' ', $node->value[1]).' const '.$node->value[0].'=');
    $this->emit($node->value[2]);
    $this->out->write(';');
  }

  protected function emitProperty($node) {
    if (isset($node->value[3])) {
      $this->out->write("\n/** @var ".$node->value[3]." */\n");
    }
    if (isset($node->value[4])) {
      $this->meta[0][self::PROPERTY][$node->value[0]]= $node->value[4];
    }
    $this->out->write(implode(' ', $node->value[1]).' $'.$node->value[0]);
    if (isset($node->value[2])) {
      $this->out->write('=');
      $this->emit($node->value[2]);
    }
    $this->out->write(';');
  }

  protected function emitMethod($node) {
    $declare= $promote= $params= '';
    foreach ($node->value[2] as $param) {
      if (isset($param[4])) {
        $declare= $param[4].' $'.$param[0].';';
        $promote.= '$this->'.$param[0].'= $'.$param[0].';';
      }
    }
    $this->out->write($declare);
    if (isset($node->value[6])) {
      $this->meta[0][self::METHOD][$node->value[0]]= $node->value[6];
    }
    $this->out->write(implode(' ', $node->value[1]).' function '.$node->value[0].'(');
    $this->params($node->value[2]);
    $this->out->write(')');
    if ($t= $this->type($node->value[4])) {
      $this->out->write(':'.$t);
    }
    if (null === $node->value[3]) {
      $this->out->write(';');
    } else {
      $this->out->write(' {'.$promote);
      $this->emit($node->value[3]);
      $this->out->write('}');
    }
  }

  protected function emitBraced($node) {
    $this->out->write('(');
    $this->emit($node->value);
    $this->out->write(')');
  }

  protected function emitBinary($node) {
    $this->emit($node->value[0]);
    $this->out->write(' '.$node->symbol->id.' ');
    $this->emit($node->value[1]);
  }

  protected function emitUnary($node) {
    $this->out->write($node->symbol->id);
    $this->emit($node->value);
  }

  protected function emitTernary($node) {
    $this->emit($node->value[0]);
    $this->out->write('?');
    $this->emit($node->value[1]);
    $this->out->write(':');
    $this->emit($node->value[1]);
  }

  protected function emitOffset($node) {
    $this->emit($node->value[0]);
    $this->out->write('[');
    $this->emit($node->value[1]);
    $this->out->write(']');
  }

  protected function emitAssignment($node) {
    $this->emit($node->value[0]);
    $this->out->write('=');
    $this->emit($node->value[1]);
  }

  protected function emitReturn($node) {
    $this->out->write('return ');
    $this->emit($node->value);
    $this->out->write(';');
  }

  protected function emitIf($node) {
    $this->out->write('if (');
    $this->emit($node->value[0]);
    $this->out->write(') {');
    $this->emit($node->value[1]);
    $this->out->write('}');

    if (isset($node->value[2])) {
      $this->out->write('else {');
      $this->emit($node->value[2]);
      $this->out->write('}');
    }
  }

  protected function emitSwitch($node) {
    $this->out->write('switch (');
    $this->emit($node->value[0]);
    $this->out->write(') {');
    foreach ($node->value[1] as $case) {
      if ($case[0]) {
        $this->out->write('case ');
        $this->emit($case[0]);
        $this->out->write(':');
      } else {
        $this->out->write('default:');
      }
      $this->emit($case[1]);
    }
    $this->out->write('}');
  }

  protected function emitTry($node) {
    $this->out->write('try {');
    $this->emit($node->value[0]);
    $this->out->write('}');
    if (isset($node->value[1])) {
      foreach ($node->value[1] as $catch) {
        $this->catches($catch);
      }
    }
    if (isset($node->value[2])) {
      $this->out->write('finally {');
      $this->emit($node->value[2]);
      $this->out->write('}');
    }
  }

  protected function emitThrow($node) {
    $this->out->write('throw ');
    $this->emit($node->value);
    $this->out->write(';');
  }

  protected function emitForeach($node) {
    $this->out->write('foreach (');
    $this->emit($node->value[0]);
    $this->out->write(' as ');
    if ($node->value[1]) {
      $this->emit($node->value[1]);
      $this->out->write(' => ');
    }
    $this->emit($node->value[2]);
    $this->out->write(')');
    if ('block' === $node->value[3]->arity) {
      $this->out->write('{');
      $this->emit($node->value[3]->value);
      $this->out->write('}');
    } else {
      $this->emit($node->value[3]);
      $this->out->write(';');
    }
  }

  protected function emitFor($node) {
    $this->out->write('for (');
    $this->arguments($node->value[0]);
    $this->out->write(';');
    $this->arguments($node->value[1]);
    $this->out->write(';');
    $this->arguments($node->value[2]);
    $this->out->write(')');
    if ('block' === $node->value[3]->arity) {
      $this->out->write('{');
      $this->emit($node->value[3]->value);
      $this->out->write('}');
    } else {
      $this->emit($node->value[3]);
      $this->out->write(';');
    }
  }

  protected function emitDo($node) {
    $this->out->write('do');
    if ('block' === $node->value[1]->arity) {
      $this->out->write('{');
      $this->emit($node->value[1]->value);
      $this->out->write('}');
    } else {
      $this->emit($node->value[1]);
      $this->out->write(';');
    }
    $this->out->write('while (');
    $this->emit($node->value[0]);
    $this->out->write(');');
  }

  protected function emitWhile($node) {
    $this->out->write('while (');
    $this->emit($node->value[0]);
    $this->out->write(')');
    if ('block' === $node->value[1]->arity) {
      $this->out->write('{');
      $this->emit($node->value[1]->value);
      $this->out->write('}');
    } else {
      $this->emit($node->value[1]);
      $this->out->write(';');
    }
  }

  protected function emitBreak($node) {
    $this->out->write('break ');
    $node->value && $this->emit($node->value);
    $this->out->write(';');
  }

  protected function emitContinue($node) {
    $this->out->write('continue ');
    $node->value && $this->emit($node->value);
    $this->out->write(';');
  }

  protected function emitNew($node) {
    if (null === $node->value[0]) {
      $this->out->write('new class(');
      $this->arguments($node->value[1]);
      $this->out->write(')');

      $definition= $node->value[2];
      $definition[2] && $this->out->write(' extends '.$definition[2]);
      $definition[3] && $this->out->write(' implements '.implode(', ', $definition[3]));
      $this->out->write('{');
      foreach ($definition[4] as $member) {
        $this->emit($member);
        $this->out->write("\n");
      }
      $this->out->write('}');
    } else {
      $this->out->write('new '.$node->value[0].'(');
      $this->arguments($node->value[1]);
      $this->out->write(')');
    }
  }

  protected function emitInvoke($node) {
    $this->emit($node->value[0]);
    $this->out->write('(');
    $this->arguments($node->value[1]);
    $this->out->write(')');
  }

  protected function emitScope($node) {
    $this->out->write($node->value[0].'::');
    $this->emit($node->value[1]);
  }

  protected function emitInstance($node) {
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

  protected function emitUnpack($node) {
    $this->out->write('...');
    $this->emit($node->value);
  }

  protected function emitYield($node) {
    $this->out->write('yield ');
    if ($node->value[0]) {
      $this->emit($node->value[0]);
      $this->out->write('=>');
    }
    if ($node->value[1]) {
      $this->emit($node->value[1]);
    }
  }

  protected function emitFrom($node) {
    $this->out->write('yield from ');
    $this->emit($node->value);
  }

  public function emit($arg) {
    if ($arg instanceof Node) {
      $this->{'emit'.$arg->arity}($arg);
    } else {
      foreach ($arg as $node) {
        $this->{'emit'.$node->arity}($node);
        isset($node->symbol->std) || $this->out->write(";\n"); 
      }
    }
  }
}