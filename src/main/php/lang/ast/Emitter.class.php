<?php namespace lang\ast;

use lang\reflect\Package;
use lang\IllegalArgumentException;
use io\streams\MemoryOutputStream;
use io\streams\StringWriter;
use lang\ast\nodes\Value;

abstract class Emitter {
  const PROPERTY = 0;
  const METHOD   = 1;

  protected $out;
  protected $line= 1;
  protected $meta= [];
  protected $unsupported= [];

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
   * Returns the qualified name for use with the XP type system
   *
   * @param  string $name E.g. `\lang\ast\Parse`
   * @return string In the above example, `lang.ast.Parse`.
   */
  protected function name($name) {
    return '\\' === $name{0} ? strtr(substr($name, 1), '\\', '.') : $name;
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

  /**
   * Returns type literal or NULL
   *
   * @param  string $name
   * @return string
   */
  protected function type($name) {
    return (
      '?' === $name{0} ||                     // nullable
      0 === strncmp($name, 'function', 8) ||  // function
      strstr($name, '|') ||                   // union
      isset($this->unsupported[$name])
    ) ? null : $name;
  }

  /**
   * Search a given scope recursively for nodes with a given arity
   *
   * @param  lang.ast.Node|lang.ast.Node[] $arg
   * @param  string $arity
   * @return iterable
   */
  protected function search($arg, $arity) {
    if ($arg instanceof Node) {         // TODO: Do we need this?
      if ($arg->arity === $arity) {
        yield $arg;
      } else {
        foreach ($this->search($arg->value, $arity) as $result) {
          yield $result;
        }
      }
    } else if ($arg instanceof Value) {  // TODO: Move recursion into Kind subclasses
      foreach ((array)$arg as $node) {
        foreach ($this->search($node, $arity) as $result) {
          yield $result;
        }
      }
    } else if (is_array($arg)) {
      foreach ($arg as $node) {
        foreach ($this->search($node, $arity) as $result) {
          yield $result;
        }
      }
    }
  }

  protected function paramType($name) {
    return $this->type($name);
  }

  protected function returnType($name) {
    return $this->type($name);
  }

  protected function catches($catch) {
    $this->out->write('catch('.implode('|', $catch->types).' $'.$catch->variable.') {');
    $this->emit($catch->body);
    $this->out->write('}');
  }

  protected function param($param) {
    if ($param[2] && $t= $this->paramType($param[2]->literal())) {
      $this->out->write($t.' ');
    }
    if ($param[3]) {
      $this->out->write('... $'.$param[0]);
    } else {
      $this->out->write(($param[1] ? '&' : '').'$'.$param[0]);
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

  protected function emitStart($kind) {
    $this->out->write('<?php ');
  }

  protected function emitPackage($kind) {
    $this->out->write('namespace '.$kind.";\n");
  }

  protected function emitImport($kind) {
    foreach ($kind as $type => $alias) {
      $this->out->write('use '.$type.($alias ? ' as '.$alias : '').';');
    }
  }

  protected function emitAnnotation($kind) {
    // NOOP
  }

  protected function emitLiteral($kind) {
    $this->out->write($kind);
  }

  protected function emitName($kind) {
    $this->out->write($kind);
  }

  protected function emitBlock($kind) {
    $this->out->write('{');
    $this->emit($kind);
    $this->out->write('}');
  }

  protected function emitStatic($kind) {
    foreach ($kind as $variable => $initial) {
      $this->out->write('static $'.$variable);
      if ($initial) {
        $this->out->write('=');
        $this->emit($initial);
      }
      $this->out->write(';');
    }
  }

  protected function emitVariable($kind) {
    $this->out->write('$'.$kind);
  }

  protected function emitCast($kind) {
    static $native= ['string' => true, 'int' => true, 'float' => true, 'bool' => true, 'array' => true, 'object' => true];

    $name= $kind->type->name();
    if ('?' === $name{0}) {
      $this->out->write('cast(');
      $this->emit($kind->expression);
      $this->out->write(',\''.$name.'\', false)');
    } else if (isset($native[$name])) {
      $this->out->write('('.$kind->type->literal().')');
      $this->emit($kind->expression);
    } else {
      $this->out->write('cast(');
      $this->emit($kind->expression);
      $this->out->write(',\''.$name.'\')');
    }
  }

  protected function emitArray($kind) {
    if (empty($kind)) {
      $this->out->write('[]');
      return;
    }

    $unpack= false;
    foreach ($kind as $pair) {
      if ('unpack' === $pair[1]->arity) {
        $unpack= true;
        break;
      }
    }

    if ($unpack) {
      $this->out->write('array_merge([');
      foreach ($kind as $pair) {
        if ($pair[0]) {
          $this->emit($pair[0]);
          $this->out->write('=>');
        }
        if ('unpack' === $pair[1]->arity) {
          if ('array' === $pair[1]->value->arity) {
            $this->out->write('],');
            $this->emit($pair[1]->value);
            $this->out->write(',[');
          } else {
            $t= $this->temp();
            $this->out->write('],('.$t.'=');
            $this->emit($pair[1]->value);
            $this->out->write(') instanceof \Traversable ? iterator_to_array('.$t.') : '.$t.',[');
          }
        } else {
          $this->emit($pair[1]);
          $this->out->write(',');
        }
      }
      $this->out->write('])');
    } else {
      $this->out->write('[');
      foreach ($kind as $pair) {
        if ($pair[0]) {
          $this->emit($pair[0]);
          $this->out->write('=>');
        }
        $this->emit($pair[1]);
        $this->out->write(',');
      }
      $this->out->write(']');
    }
  }

  protected function emitFunction($kind) {
    $this->out->write('function '.$kind->name.'('); 
    $this->params($kind->signature[0]);
    $this->out->write(')');
    if ($t= $this->returnType($kind->signature[1])) {
      $this->out->write(':'.$t);
    }
    $this->out->write('{');
    $this->emit($kind->body);
    $this->out->write('}');
  }

  protected function emitClosure($kind) {
    $this->out->write('function('); 
    $this->params($kind->signature[0]);
    $this->out->write(')');
    if ($kind->signature[1] && $t= $this->returnType($kind->signature[1]->literal())) {
      $this->out->write(':'.$t);
    }
    if ($kind->use) {
      $this->out->write(' use('.implode(',', $kind->use).') ');
    }
    $this->out->write('{');
    $this->emit($kind->body);
    $this->out->write('}');
  }

  protected function emitLambda($kind) {
    $this->out->write('function('); 
    $this->params($kind->signature[0]);
    $this->out->write(')');
    if ($kind->signature[1] && $t= $this->returnType($kind->signature[1]->literal())) {
      $this->out->write(':'.$t);
    }

    $capture= [];
    foreach ($this->search($kind->body, 'variable') as $var) {
      $capture[$var->value]= true;
    }
    unset($capture['this']);
    foreach ($kind->signature[0] as $param) {
      unset($capture[$param[0]]);
    }
    $capture && $this->out->write(' use($'.implode(', $', array_keys($capture)).')');

    $this->out->write('{ return ');
    $this->emit($kind->body);
    $this->out->write('; }');
  }

  protected function emitClass($kind) {
    array_unshift($this->meta, []);

    $this->out->write(implode(' ', $kind->modifiers).' class '.$this->declaration($kind->name));
    $kind->parent && $this->out->write(' extends '.$kind->parent);
    $kind->implements && $this->out->write(' implements '.implode(', ', $kind->implements));
    $this->out->write('{');
    foreach ($kind->body as $member) {
      $this->emit($member);
    }

    $this->out->write('static function __init() {');
    $this->emitMeta($kind->name, $kind->annotations, $kind->comment);
    $this->out->write('}} '.$kind->name.'::__init();');
  }

  protected function emitMeta($name, $annotations, $comment) {
    $this->out->write('\xp::$meta[\''.$this->name($name).'\']= [');
    $this->out->write('"class" => [DETAIL_ANNOTATIONS => [');
    $this->annotations($annotations);
    $this->out->write('], DETAIL_COMMENT => \''.$comment.'\'],');

    foreach (array_shift($this->meta) as $type => $lookup) {
      $this->out->write($type.' => [');
      foreach ($lookup as $key => $meta) {
        $this->out->write("'".$key."' => [DETAIL_ANNOTATIONS => [");
        $this->annotations($meta[DETAIL_ANNOTATIONS]);
        $this->out->write('], DETAIL_TARGET_ANNO => [');
        foreach ($meta[DETAIL_TARGET_ANNO] as $target => $annotations) {
          $this->out->write("'$".$target."' => [");
          $this->annotations($annotations);
          $this->out->write('],');
        }
        $this->out->write('], DETAIL_RETURNS => \''.$meta[DETAIL_RETURNS].'\'');
        $this->out->write(', DETAIL_COMMENT => \''.$meta[DETAIL_COMMENT].'\'');
        $this->out->write(', DETAIL_ARGUMENTS => [\''.implode('\', \'', $meta[DETAIL_ARGUMENTS]).'\']],');
      }
      $this->out->write('],');
    }
    $this->out->write('];');
  }

  protected function emitInterface($kind) {
    array_unshift($this->meta, []);

    $this->out->write('interface '.$this->declaration($kind->name));
    $kind->parents && $this->out->write(' extends '.implode(', ', $kind->parents));
    $this->out->write('{');
    foreach ($kind->body as $member) {
      $this->emit($member);
      $this->out->write("\n");
    }
    $this->out->write('}');

    $this->emitMeta($kind->name, $kind->annotations, $kind->comment);
  }

  protected function emitTrait($kind) {
    array_unshift($this->meta, []);

    $this->out->write('trait '.$this->declaration($kind->name));
    $this->out->write('{');
    foreach ($kind->body as $member) {
      $this->emit($member);
      $this->out->write("\n");
    }

    $this->out->write('static function __init() {');
    $this->emitMeta($kind->name, $kind->annotations, $kind->comment);
    $this->out->write('}} '.$kind->name.'::__init();');
  }

  protected function emitUse($kind) {
    $this->out->write('use '.implode(',', $kind->types));
    if ($kind->aliases) {
      $this->out->write('{');
      foreach ($kind->aliases as $reference => $alias) {
        $this->out->write($reference.' as '.$alias.';');
      }
      $this->out->write('}');
    } else {
      $this->out->write(';');
    }
  }

  protected function emitConst($kind) {
    $this->out->write(implode(' ', $kind->modifiers).' const '.$kind->name.'=');
    $this->emit($kind->expression);
    $this->out->write(';');
  }

  protected function emitProperty($kind) {
    $this->meta[0][self::PROPERTY][$kind->name]= [
      DETAIL_RETURNS     => $kind->type ? $kind->type->name() : 'var',
      DETAIL_ANNOTATIONS => $kind->annotations ? $kind->annotations : [],
      DETAIL_COMMENT     => $kind->comment,
      DETAIL_TARGET_ANNO => [],
      DETAIL_ARGUMENTS   => []
    ];

    $this->out->write(implode(' ', $kind->modifiers).' $'.$kind->name);
    if (isset($kind->expression)) {
      $this->out->write('=');
      $this->emit($kind->expression);
    }
    $this->out->write(';');
  }

  protected function emitMethod($kind) {
    $meta= [
      DETAIL_RETURNS     => $kind->signature[1] ? $kind->signature[1]->name() : 'var',
      DETAIL_ANNOTATIONS => isset($kind->annotations) ? $kind->annotations : [],
      DETAIL_COMMENT     => $kind->comment,
      DETAIL_TARGET_ANNO => [],
      DETAIL_ARGUMENTS   => []
    ];

    $declare= $promote= $params= '';
    foreach ($kind->signature[0] as $param) {
      if (isset($param[4])) {
        $declare.= $param[4].' $'.$param[0].';';
        $promote.= '$this->'.$param[0].'= $'.$param[0].';';
        $this->meta[0][self::PROPERTY][$param[0]]= [
          DETAIL_RETURNS     => $param[2] ? $param[2]->name() : 'var',
          DETAIL_ANNOTATIONS => [],
          DETAIL_COMMENT     => null,
          DETAIL_TARGET_ANNO => [],
          DETAIL_ARGUMENTS   => []
        ];
      }
      $meta[DETAIL_TARGET_ANNO][$param[0]]= $param[6];
      $meta[DETAIL_ARGUMENTS][]= $param[2] ? $param[2]->name() : 'var';
    }
    $this->out->write($declare);
    $this->out->write(implode(' ', $kind->modifiers).' function '.$kind->name.'(');
    $this->params($kind->signature[0]);
    $this->out->write(')');
    if ($kind->signature[1] && $t= $this->returnType($kind->signature[1]->literal())) {
      $this->out->write(':'.$t);
    }
    if (null === $kind->body) {
      $this->out->write(';');
    } else {
      $this->out->write(' {'.$promote);
      $this->emit($kind->body);
      $this->out->write('}');
    }

    $this->meta[0][self::METHOD][$kind->name]= $meta;
  }

  protected function emitBraced($kind) {
    $this->out->write('(');
    $this->emit($kind);
    $this->out->write(')');
  }

  protected function emitBinary($kind) {
    $this->emit($kind->left);
    $this->out->write(' '.$kind->operator.' ');
    $this->emit($kind->right);
  }

  protected function emitUnary($kind) {
    $this->out->write($kind->operator);
    $this->emit($kind->expression);
  }

  protected function emitTernary($kind) {
    $this->emit($kind->condition);
    $this->out->write('?');
    $this->emit($kind->expression);
    $this->out->write(':');
    $this->emit($kind->otherwise);
  }

  protected function emitOffset($kind) {
    $this->emit($kind->expression);
    if (null === $kind->offset) {
      $this->out->write('[]');
    } else {
      $this->out->write('[');
      $this->emit($kind->offset);
      $this->out->write(']');
    }
  }

  protected function emitAssignment($kind) {
    $this->emit($kind->variable);
    $this->out->write($kind->operator);
    $this->emit($kind->expression);
  }

  protected function emitReturn($kind) {
    $this->out->write('return ');
    $this->emit($kind);
    $this->out->write(';');
  }

  protected function emitIf($kind) {
    $this->out->write('if (');
    $this->emit($kind->expression);
    $this->out->write(') {');
    $this->emit($kind->body);
    $this->out->write('}');

    if (isset($kind->otherwise)) {
      $this->out->write('else {');
      $this->emit($kind->otherwise);
      $this->out->write('}');
    }
  }

  protected function emitSwitch($kind) {
    $this->out->write('switch (');
    $this->emit($kind->expression);
    $this->out->write(') {');
    foreach ($kind->cases as $case) {
      if ($case->expression) {
        $this->out->write('case ');
        $this->emit($case->expression);
        $this->out->write(':');
      } else {
        $this->out->write('default:');
      }
      $this->emit($case->body);
    }
    $this->out->write('}');
  }

  protected function emitTry($kind) {
    $this->out->write('try {');
    $this->emit($kind->body);
    $this->out->write('}');
    if (isset($kind->catches)) {
      foreach ($kind->catches as $catch) {
        $this->catches($catch);
      }
    }
    if (isset($kind->finally)) {
      $this->out->write('finally {');
      $this->emit($kind->finally);
      $this->out->write('}');
    }
  }

  protected function emitThrow($kind) {
    $this->out->write('throw ');
    $this->emit($kind);
    $this->out->write(';');
  }

  protected function emitForeach($kind) {
    $this->out->write('foreach (');
    $this->emit($kind->expression);
    $this->out->write(' as ');
    if ($kind->ley) {
      $this->emit($kind->ley);
      $this->out->write(' => ');
    }
    $this->emit($kind->value);
    $this->out->write(')');
    if ('block' === $kind->body->arity) {
      $this->out->write('{');
      $this->emit($kind->body->value);
      $this->out->write('}');
    } else {
      $this->emit($kind->body);
      $this->out->write(';');
    }
  }

  protected function emitFor($kind) {
    $this->out->write('for (');
    $this->arguments($kind->initialization);
    $this->out->write(';');
    $this->arguments($kind->condition);
    $this->out->write(';');
    $this->arguments($kind->loop);
    $this->out->write(')');
    if ('block' === $kind->body->arity) {
      $this->out->write('{');
      $this->emit($kind->body->value);
      $this->out->write('}');
    } else {
      $this->emit($kind->body);
      $this->out->write(';');
    }
  }

  protected function emitDo($kind) {
    $this->out->write('do');
    if ('block' === $kind->body->arity) {
      $this->out->write('{');
      $this->emit($kind->body->value);
      $this->out->write('}');
    } else {
      $this->emit($kind->body);
      $this->out->write(';');
    }
    $this->out->write('while (');
    $this->emit($kind->expression);
    $this->out->write(');');
  }

  protected function emitWhile($kind) {
    $this->out->write('while (');
    $this->emit($kind->expression);
    $this->out->write(')');
    if ('block' === $kind->body->arity) {
      $this->out->write('{');
      $this->emit($kind->body->value);
      $this->out->write('}');
    } else {
      $this->emit($kind->body);
      $this->out->write(';');
    }
  }

  protected function emitBreak($kind) {
    $this->out->write('break ');
    $kind && $this->emit($kind);
    $this->out->write(';');
  }

  protected function emitContinue($kind) {
    $this->out->write('continue ');
    $kind && $this->emit($kind);
    $this->out->write(';');
  }

  protected function emitInstanceOf($kind) {
    $this->emit($kind->expression);
    $this->out->write(' instanceof ');
    if ($kind->type instanceof Node) {
      $this->emit($kind->type);
    } else {
      $this->out->write($kind->type);
    }
  }

  protected function emitNew($kind) {
    if ($kind->type instanceof Value) {
      $this->out->write('new class(');
      $this->arguments($kind->arguments);
      $this->out->write(')');

      $definition= $kind->type;
      $definition->parent && $this->out->write(' extends '.$definition->parent);
      $definition->implements && $this->out->write(' implements '.implode(', ', $definition->implements));
      $this->out->write('{');
      foreach ($definition->body as $member) {
        $this->emit($member);
        $this->out->write("\n");
      }
      $this->out->write('}');
    } else {
      $this->out->write('new '.$kind->type.'(');
      $this->arguments($kind->arguments);
      $this->out->write(')');
    }
  }

  protected function emitInvoke($kind) {
    $this->emit($kind->expression);
    $this->out->write('(');
    $this->arguments($kind->arguments);
    $this->out->write(')');
  }

  protected function emitScope($kind) {
    $this->out->write($kind->type.'::');
    $this->emit($kind->member);
  }

  protected function emitInstance($kind) {
    if ('new' === $kind->expression->arity) {
      $this->out->write('(');
      $this->emit($kind->expression);
      $this->out->write(')->');
    } else {
      $this->emit($kind->expression);
      $this->out->write('->');
    }
    $this->emit($kind->member);
  }

  protected function emitUnpack($kind) {
    $this->out->write('...');
    $this->emit($kind);
  }

  protected function emitYield($kind) {
    $this->out->write('yield ');
    if ($kind->key) {
      $this->emit($kind->key);
      $this->out->write('=>');
    }
    if ($kind->value) {
      $this->emit($kind->value);
    }
  }

  protected function emitFrom($kind) {
    $this->out->write('yield from ');
    $this->emit($kind);
  }

  public function emit($arg) {
    if ($arg instanceof Node) {
      while ($arg->line > $this->line) {
        $this->out->write("\n");
        $this->line++;
      }
      $this->{'emit'.$arg->arity}($arg->value);
    } else {
      foreach ($arg as $node) {
        while ($node->line > $this->line) {
          $this->out->write("\n");
          $this->line++;
        }
        $this->{'emit'.$node->arity}($node->value);
        isset($node->symbol->std) || $this->out->write(';');
      }
    }
  }
}