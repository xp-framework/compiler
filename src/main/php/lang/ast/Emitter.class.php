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

  protected function emitStart($start) {
    $this->out->write('<?php ');
  }

  protected function emitPackage($package) {
    $this->out->write('namespace '.$package.";\n");
  }

  protected function emitImport($import) {
    foreach ($import as $type => $alias) {
      $this->out->write('use '.$type.($alias ? ' as '.$alias : '').';');
    }
  }

  protected function emitAnnotation($annotations) {
    // NOOP
  }

  protected function emitLiteral($literal) {
    $this->out->write($literal);
  }

  protected function emitName($name) {
    $this->out->write($name);
  }

  protected function emitBlock($block) {
    $this->out->write('{');
    $this->emit($block);
    $this->out->write('}');
  }

  protected function emitStatic($static) {
    foreach ($static as $variable => $initial) {
      $this->out->write('static $'.$variable);
      if ($initial) {
        $this->out->write('=');
        $this->emit($initial);
      }
      $this->out->write(';');
    }
  }

  protected function emitVariable($variable) {
    $this->out->write('$'.$variable);
  }

  protected function emitCast($cast) {
    static $native= ['string' => true, 'int' => true, 'float' => true, 'bool' => true, 'array' => true, 'object' => true];

    $name= $cast->type->name();
    if ('?' === $name{0}) {
      $this->out->write('cast(');
      $this->emit($cast->expression);
      $this->out->write(',\''.$name.'\', false)');
    } else if (isset($native[$name])) {
      $this->out->write('('.$cast->type->literal().')');
      $this->emit($cast->expression);
    } else {
      $this->out->write('cast(');
      $this->emit($cast->expression);
      $this->out->write(',\''.$name.'\')');
    }
  }

  protected function emitArray($array) {
    if (empty($array)) {
      $this->out->write('[]');
      return;
    }

    $unpack= false;
    foreach ($array as $pair) {
      if ('unpack' === $pair[1]->arity) {
        $unpack= true;
        break;
      }
    }

    if ($unpack) {
      $this->out->write('array_merge([');
      foreach ($array as $pair) {
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
      foreach ($array as $pair) {
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

  protected function emitFunction($function) {
    $this->out->write('function '.$function->name.'('); 
    $this->params($function->signature[0]);
    $this->out->write(')');
    if ($t= $this->returnType($function->signature[1])) {
      $this->out->write(':'.$t);
    }
    $this->out->write('{');
    $this->emit($function->body);
    $this->out->write('}');
  }

  protected function emitClosure($closure) {
    $this->out->write('function('); 
    $this->params($closure->signature[0]);
    $this->out->write(')');
    if ($closure->signature[1] && $t= $this->returnType($closure->signature[1]->literal())) {
      $this->out->write(':'.$t);
    }
    if ($closure->use) {
      $this->out->write(' use('.implode(',', $closure->use).') ');
    }
    $this->out->write('{');
    $this->emit($closure->body);
    $this->out->write('}');
  }

  protected function emitLambda($lambda) {
    $this->out->write('function('); 
    $this->params($lambda->signature[0]);
    $this->out->write(')');
    if ($lambda->signature[1] && $t= $this->returnType($lambda->signature[1]->literal())) {
      $this->out->write(':'.$t);
    }

    $capture= [];
    foreach ($this->search($lambda->body, 'variable') as $var) {
      $capture[$var->value]= true;
    }
    unset($capture['this']);
    foreach ($lambda->signature[0] as $param) {
      unset($capture[$param[0]]);
    }
    $capture && $this->out->write(' use($'.implode(', $', array_keys($capture)).')');

    $this->out->write('{ return ');
    $this->emit($lambda->body);
    $this->out->write('; }');
  }

  protected function emitClass($class) {
    array_unshift($this->meta, []);

    $this->out->write(implode(' ', $class->modifiers).' class '.$this->declaration($class->name));
    $class->parent && $this->out->write(' extends '.$class->parent);
    $class->implements && $this->out->write(' implements '.implode(', ', $class->implements));
    $this->out->write('{');
    foreach ($class->body as $member) {
      $this->emit($member);
    }

    $this->out->write('static function __init() {');
    $this->emitMeta($class->name, $class->annotations, $class->comment);
    $this->out->write('}} '.$class->name.'::__init();');
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

  protected function emitInterface($interface) {
    array_unshift($this->meta, []);

    $this->out->write('interface '.$this->declaration($interface->name));
    $interface->parents && $this->out->write(' extends '.implode(', ', $interface->parents));
    $this->out->write('{');
    foreach ($interface->body as $member) {
      $this->emit($member);
      $this->out->write("\n");
    }
    $this->out->write('}');

    $this->emitMeta($interface->name, $interface->annotations, $interface->comment);
  }

  protected function emitTrait($trait) {
    array_unshift($this->meta, []);

    $this->out->write('trait '.$this->declaration($trait->name));
    $this->out->write('{');
    foreach ($trait->body as $member) {
      $this->emit($member);
      $this->out->write("\n");
    }

    $this->out->write('static function __init() {');
    $this->emitMeta($trait->name, $trait->annotations, $trait->comment);
    $this->out->write('}} '.$trait->name.'::__init();');
  }

  protected function emitUse($use) {
    $this->out->write('use '.implode(',', $use->types));
    if ($use->aliases) {
      $this->out->write('{');
      foreach ($use->aliases as $reference => $alias) {
        $this->out->write($reference.' as '.$alias.';');
      }
      $this->out->write('}');
    } else {
      $this->out->write(';');
    }
  }

  protected function emitConst($const) {
    $this->out->write(implode(' ', $const->modifiers).' const '.$const->name.'=');
    $this->emit($const->expression);
    $this->out->write(';');
  }

  protected function emitProperty($property) {
    $this->meta[0][self::PROPERTY][$property->name]= [
      DETAIL_RETURNS     => $property->type ? $property->type->name() : 'var',
      DETAIL_ANNOTATIONS => $property->annotations ? $property->annotations : [],
      DETAIL_COMMENT     => $property->comment,
      DETAIL_TARGET_ANNO => [],
      DETAIL_ARGUMENTS   => []
    ];

    $this->out->write(implode(' ', $property->modifiers).' $'.$property->name);
    if (isset($property->expression)) {
      $this->out->write('=');
      $this->emit($property->expression);
    }
    $this->out->write(';');
  }

  protected function emitMethod($method) {
    $meta= [
      DETAIL_RETURNS     => $method->signature[1] ? $method->signature[1]->name() : 'var',
      DETAIL_ANNOTATIONS => isset($method->annotations) ? $method->annotations : [],
      DETAIL_COMMENT     => $method->comment,
      DETAIL_TARGET_ANNO => [],
      DETAIL_ARGUMENTS   => []
    ];

    $declare= $promote= $params= '';
    foreach ($method->signature[0] as $param) {
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
    $this->out->write(implode(' ', $method->modifiers).' function '.$method->name.'(');
    $this->params($method->signature[0]);
    $this->out->write(')');
    if ($method->signature[1] && $t= $this->returnType($method->signature[1]->literal())) {
      $this->out->write(':'.$t);
    }
    if (null === $method->body) {
      $this->out->write(';');
    } else {
      $this->out->write(' {'.$promote);
      $this->emit($method->body);
      $this->out->write('}');
    }

    $this->meta[0][self::METHOD][$method->name]= $meta;
  }

  protected function emitBraced($braced) {
    $this->out->write('(');
    $this->emit($braced);
    $this->out->write(')');
  }

  protected function emitBinary($binary) {
    $this->emit($binary->left);
    $this->out->write(' '.$binary->operator.' ');
    $this->emit($binary->right);
  }

  protected function emitUnary($unary) {
    $this->out->write($unary->operator);
    $this->emit($unary->expression);
  }

  protected function emitTernary($ternary) {
    $this->emit($ternary->condition);
    $this->out->write('?');
    $this->emit($ternary->expression);
    $this->out->write(':');
    $this->emit($ternary->otherwise);
  }

  protected function emitOffset($offset) {
    $this->emit($offset->expression);
    if (null === $offset->offset) {
      $this->out->write('[]');
    } else {
      $this->out->write('[');
      $this->emit($offset->offset);
      $this->out->write(']');
    }
  }

  protected function emitAssignment($assignment) {
    $this->emit($assignment->variable);
    $this->out->write($assignment->operator);
    $this->emit($assignment->expression);
  }

  protected function emitReturn($return) {
    $this->out->write('return ');
    $this->emit($return);
    $this->out->write(';');
  }

  protected function emitIf($if) {
    $this->out->write('if (');
    $this->emit($if->expression);
    $this->out->write(') {');
    $this->emit($if->body);
    $this->out->write('}');

    if (isset($if->otherwise)) {
      $this->out->write('else {');
      $this->emit($if->otherwise);
      $this->out->write('}');
    }
  }

  protected function emitSwitch($switch) {
    $this->out->write('switch (');
    $this->emit($switch->expression);
    $this->out->write(') {');
    foreach ($switch->cases as $case) {
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

  protected function emitTry($try) {
    $this->out->write('try {');
    $this->emit($try->body);
    $this->out->write('}');
    if (isset($try->catches)) {
      foreach ($try->catches as $catch) {
        $this->catches($catch);
      }
    }
    if (isset($try->finally)) {
      $this->out->write('finally {');
      $this->emit($try->finally);
      $this->out->write('}');
    }
  }

  protected function emitThrow($throw) {
    $this->out->write('throw ');
    $this->emit($throw);
    $this->out->write(';');
  }

  protected function emitForeach($foreach) {
    $this->out->write('foreach (');
    $this->emit($foreach->expression);
    $this->out->write(' as ');
    if ($foreach->ley) {
      $this->emit($foreach->ley);
      $this->out->write(' => ');
    }
    $this->emit($foreach->value);
    $this->out->write(')');
    if ('block' === $foreach->body->arity) {
      $this->out->write('{');
      $this->emit($foreach->body->value);
      $this->out->write('}');
    } else {
      $this->emit($foreach->body);
      $this->out->write(';');
    }
  }

  protected function emitFor($for) {
    $this->out->write('for (');
    $this->arguments($for->initialization);
    $this->out->write(';');
    $this->arguments($for->condition);
    $this->out->write(';');
    $this->arguments($for->loop);
    $this->out->write(')');
    if ('block' === $for->body->arity) {
      $this->out->write('{');
      $this->emit($for->body->value);
      $this->out->write('}');
    } else {
      $this->emit($for->body);
      $this->out->write(';');
    }
  }

  protected function emitDo($do) {
    $this->out->write('do');
    if ('block' === $do->body->arity) {
      $this->out->write('{');
      $this->emit($do->body->value);
      $this->out->write('}');
    } else {
      $this->emit($do->body);
      $this->out->write(';');
    }
    $this->out->write('while (');
    $this->emit($do->expression);
    $this->out->write(');');
  }

  protected function emitWhile($while) {
    $this->out->write('while (');
    $this->emit($while->expression);
    $this->out->write(')');
    if ('block' === $while->body->arity) {
      $this->out->write('{');
      $this->emit($while->body->value);
      $this->out->write('}');
    } else {
      $this->emit($while->body);
      $this->out->write(';');
    }
  }

  protected function emitBreak($break) {
    $this->out->write('break ');
    $break && $this->emit($break);
    $this->out->write(';');
  }

  protected function emitContinue($continue) {
    $this->out->write('continue ');
    $continue && $this->emit($continue);
    $this->out->write(';');
  }

  protected function emitInstanceOf($instanceof) {
    $this->emit($instanceof->expression);
    $this->out->write(' instanceof ');
    if ($instanceof->type instanceof Node) {
      $this->emit($instanceof->type);
    } else {
      $this->out->write($instanceof->type);
    }
  }

  protected function emitNew($new) {
    if ($new->type instanceof Value) {
      $this->out->write('new class(');
      $this->arguments($new->arguments);
      $this->out->write(')');

      $definition= $new->type;
      $definition->parent && $this->out->write(' extends '.$definition->parent);
      $definition->implements && $this->out->write(' implements '.implode(', ', $definition->implements));
      $this->out->write('{');
      foreach ($definition->body as $member) {
        $this->emit($member);
        $this->out->write("\n");
      }
      $this->out->write('}');
    } else {
      $this->out->write('new '.$new->type.'(');
      $this->arguments($new->arguments);
      $this->out->write(')');
    }
  }

  protected function emitInvoke($invoke) {
    $this->emit($invoke->expression);
    $this->out->write('(');
    $this->arguments($invoke->arguments);
    $this->out->write(')');
  }

  protected function emitScope($scope) {
    $this->out->write($scope->type.'::');
    $this->emit($scope->member);
  }

  protected function emitInstance($instance) {
    if ('new' === $instance->expression->arity) {
      $this->out->write('(');
      $this->emit($instance->expression);
      $this->out->write(')->');
    } else {
      $this->emit($instance->expression);
      $this->out->write('->');
    }
    $this->emit($instance->member);
  }

  protected function emitUnpack($unpack) {
    $this->out->write('...');
    $this->emit($unpack);
  }

  protected function emitYield($yield) {
    $this->out->write('yield ');
    if ($yield->key) {
      $this->emit($yield->key);
      $this->out->write('=>');
    }
    if ($yield->value) {
      $this->emit($yield->value);
    }
  }

  protected function emitFrom($from) {
    $this->out->write('yield from ');
    $this->emit($from);
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