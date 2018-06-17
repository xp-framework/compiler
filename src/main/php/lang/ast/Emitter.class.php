<?php namespace lang\ast;

use io\streams\MemoryOutputStream;
use io\streams\StringWriter;
use lang\IllegalArgumentException;
use lang\ast\nodes\Value;
use lang\reflect\Package;

abstract class Emitter {
  const PROPERTY = 0;
  const METHOD   = 1;

  protected $out;
  protected $line= 1;
  protected $meta= [];
  protected $unsupported= [];
  protected $transformations= [];
  protected $locals= [];
  protected $stack= [];

  /**
   * Selects the correct emitter for a given runtime
   *
   * @param  string $runtime E.g. "PHP.".PHP_VERSION
   * @return self
   * @throws lang.IllegalArgumentException
   */
  public static function forRuntime($runtime) {
    sscanf($runtime, '%[^.].%d.%d', $engine, $major, $minor);
    $p= Package::forName('lang.ast.emit');

    do {
      $impl= $engine.$major.$minor;
      if ($p->providesClass($impl)) return $p->loadClass($impl);
    } while ($minor-- > 0);

    throw new IllegalArgumentException('XP Compiler does not support '.$runtime.' yet');
  }

  /** @param io.streams.Writer */
  public function __construct($out) {
    $this->out= $out;
    $this->id= 0;
  }

  public function transform($kind, $function) {
    $this->transformations[$kind]= $function;
    return $this;
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
   * Search a given scope recursively for nodes with a given kind
   *
   * @param  lang.ast.Node|lang.ast.Node[] $arg
   * @param  string $kind
   * @return iterable
   */
  protected function search($arg, $kind) {
    if ($arg instanceof Node) {         // TODO: Do we need this?
      if ($arg->kind === $kind) {
        yield $arg;
      } else {
        foreach ($this->search($arg->value, $kind) as $result) {
          yield $result;
        }
      }
    } else if ($arg instanceof Value) {  // TODO: Move recursion into Kind subclasses
      foreach ((array)$arg as $node) {
        foreach ($this->search($node, $kind) as $result) {
          yield $result;
        }
      }
    } else if (is_array($arg)) {
      foreach ($arg as $node) {
        foreach ($this->search($node, $kind) as $result) {
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

  protected function emitImportConst($import) {
    foreach ($import as $type => $alias) {
      $this->out->write('use const '.$type.($alias ? ' as '.$alias : '').';');
    }
  }

  protected function emitImportFunction($import) {
    foreach ($import as $type => $alias) {
      $this->out->write('use function '.$type.($alias ? ' as '.$alias : '').';');
    }
  }

  protected function emitAnnotation($annotations) {
    // NOOP
  }

  protected function emitCode($code) {
    $this->out->write($code);
  }

  protected function emitLiteral($literal) {
    $this->out->write($literal);
  }

  protected function emitName($name) {
    $this->out->write($name);
  }

  protected function emitEcho($echo) {
    $this->out->write('echo ');
    $s= sizeof($echo) - 1;
    foreach ($echo as $i => $expr) {
      $this->emit($expr);
      if ($i < $s) $this->out->write(',');
    }
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
      if ('unpack' === $pair[1]->kind) {
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
        if ('unpack' === $pair[1]->kind) {
          if ('array' === $pair[1]->value->kind) {
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

  protected function emitParameter($parameter) {
    if ($parameter->type && $t= $this->paramType($parameter->type->literal())) {
      $this->out->write($t.' ');
    }
    if ($parameter->variadic) {
      $this->out->write('... $'.$parameter->name);
    } else {
      $this->out->write(($parameter->reference ? '&' : '').'$'.$parameter->name);
    }
    if ($parameter->default) {
      $this->out->write('=');
      $this->emit($parameter->default);
    }
    $this->locals[$parameter->name]= true;
  }

  protected function emitSignature($signature) {
    $this->out->write('(');
    $s= sizeof($signature->parameters) - 1;
    foreach ($signature->parameters as $i => $parameter) {
      $this->emitParameter($parameter);
      if ($i < $s) $this->out->write(', ');
    }
    $this->out->write(')');

    if ($signature->returns && $t= $this->returnType($signature->returns->literal())) {
      $this->out->write(':'.$t);
    }
  }

  protected function emitFunction($function) {
    $this->stack[]= $this->locals;
    $this->locals= [];

    $this->out->write('function '.$function->name); 
    $this->emitSignature($function->signature);

    $this->out->write('{');
    $this->emit($function->body);
    $this->out->write('}');

    $this->locals= array_pop($this->stack);
  }

  protected function emitClosure($closure) {
    $this->stack[]= $this->locals;
    $this->locals= [];

    $this->out->write('function'); 
    $this->emitSignature($closure->signature);

    if ($closure->use) {
      $this->out->write(' use('.implode(',', $closure->use).') ');
      foreach ($closure->use as $name) {
        $this->locals[substr($name, 1)]= true;
      }
    }
    $this->out->write('{');
    $this->emit($closure->body);
    $this->out->write('}');

    $this->locals= array_pop($this->stack);
  }

  protected function emitLambda($lambda) {
    $capture= [];
    foreach ($this->search($lambda->body, 'variable') as $var) {
      if (isset($this->locals[$var->value])) {
        $capture[$var->value]= true;
      }
    }
    unset($capture['this']);

    $this->stack[]= $this->locals;
    $this->locals= [];

    $this->out->write('function');
    $this->emitSignature($lambda->signature);
    foreach ($lambda->signature->parameters as $param) {
      unset($capture[$param->name]);
    }

    if ($capture) {
      $this->out->write(' use($'.implode(', $', array_keys($capture)).')');
      foreach ($capture as $name => $_) {
        $this->locals[$name]= true;
      }
    }

    if (is_array($lambda->body)) {
      $this->out->write('{');
      $this->emit($lambda->body);
      $this->out->write('}');
    } else {
      $this->out->write('{ return ');
      $this->emit($lambda->body);
      $this->out->write('; }');
    }

    $this->locals= array_pop($this->stack);
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

  protected function emitAnnotations($annotations) {
    foreach ($annotations as $name => $annotation) {
      $this->out->write("'".$name."' => ");
      if ($annotation) {
        $this->emit($annotation);
        $this->out->write(',');
      } else {
        $this->out->write('null,');
      }
    }
  }

  protected function emitMeta($name, $annotations, $comment) {
    $this->out->write('\xp::$meta[\''.strtr(ltrim($name, '\\'), '\\', '.').'\']= [');
    $this->out->write('"class" => [DETAIL_ANNOTATIONS => [');
    $this->emitAnnotations($annotations);
    $this->out->write('], DETAIL_COMMENT => \''.str_replace("'", "\\'", $comment).'\'],');

    foreach (array_shift($this->meta) as $type => $lookup) {
      $this->out->write($type.' => [');
      foreach ($lookup as $key => $meta) {
        $this->out->write("'".$key."' => [DETAIL_ANNOTATIONS => [");
        $this->emitAnnotations($meta[DETAIL_ANNOTATIONS]);
        $this->out->write('], DETAIL_TARGET_ANNO => [');
        foreach ($meta[DETAIL_TARGET_ANNO] as $target => $annotations) {
          $this->out->write("'$".$target."' => [");
          $this->emitAnnotations($annotations);
          $this->out->write('],');
        }
        $this->out->write('], DETAIL_RETURNS => \''.$meta[DETAIL_RETURNS].'\'');
        $this->out->write(', DETAIL_COMMENT => \''.str_replace("'", "\\'", $meta[DETAIL_COMMENT]).'\'');
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
    $this->stack[]= $this->locals;
    $this->locals= ['this' => true];
    $meta= [
      DETAIL_RETURNS     => $method->signature->returns ? $method->signature->returns->name() : 'var',
      DETAIL_ANNOTATIONS => isset($method->annotations) ? $method->annotations : [],
      DETAIL_COMMENT     => $method->comment,
      DETAIL_TARGET_ANNO => [],
      DETAIL_ARGUMENTS   => []
    ];

    $declare= $promote= $params= '';
    foreach ($method->signature->parameters as $param) {
      if (isset($param->promote)) {
        $declare.= $param->promote.' $'.$param->name.';';
        $promote.= '$this->'.$param->name.'= $'.$param->name.';';
        $this->meta[0][self::PROPERTY][$param->name]= [
          DETAIL_RETURNS     => $param->type ? $param->type->name() : 'var',
          DETAIL_ANNOTATIONS => [],
          DETAIL_COMMENT     => null,
          DETAIL_TARGET_ANNO => [],
          DETAIL_ARGUMENTS   => []
        ];
      }
      $meta[DETAIL_TARGET_ANNO][$param->name]= $param->annotations;
      $meta[DETAIL_ARGUMENTS][]= $param->type ? $param->type->name() : 'var';
    }
    $this->out->write($declare);
    $this->out->write(implode(' ', $method->modifiers).' function '.$method->name);
    $this->emitSignature($method->signature);

    if (null === $method->body) {
      $this->out->write(';');
    } else {
      $this->out->write(' {'.$promote);
      $this->emit($method->body);
      $this->out->write('}');
    }

    $this->meta[0][self::METHOD][$method->name]= $meta;
    $this->locals= array_pop($this->stack);
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

  protected function emitAssign($target) {
    if ('variable' === $target->kind) {
      $this->out->write('$'.$target->value);
      $this->locals[$target->value]= true;
    } else if ('array' === $target->kind) {
      $this->out->write('[');
      foreach ($target->value as $pair) {
        $this->emitAssign($pair[1]);
        $this->out->write(',');
      }
      $this->out->write(']');
    } else {
      $this->emit($target);
    }
  }

  protected function emitAssignment($assignment) {
    $this->emitAssign($assignment->variable);
    $this->out->write($assignment->operator);
    $this->emit($assignment->expression);
  }

  protected function emitReturn($return) {
    $this->out->write('return ');
    $return && $this->emit($return);
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

  protected function emitCatch($catch) {
    if (empty($catch->types)) {
      $this->out->write('catch(\\Throwable $'.$catch->variable.') {');
    } else {
      $this->out->write('catch('.implode('|', $catch->types).' $'.$catch->variable.') {');
    }
    $this->emit($catch->body);
    $this->out->write('}');
  }

  protected function emitTry($try) {
    $this->out->write('try {');
    $this->emit($try->body);
    $this->out->write('}');
    if (isset($try->catches)) {
      foreach ($try->catches as $catch) {
        $this->emitCatch($catch);
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
    if ($foreach->key) {
      $this->emit($foreach->key);
      $this->out->write(' => ');
    }
    $this->emit($foreach->value);
    $this->out->write(') {');
    $this->emit($foreach->body);
    $this->out->write('}');
  }

  protected function emitFor($for) {
    $this->out->write('for (');
    $this->emitArguments($for->initialization);
    $this->out->write(';');
    $this->emitArguments($for->condition);
    $this->out->write(';');
    $this->emitArguments($for->loop);
    $this->out->write(') {');
    $this->emit($for->body);
    $this->out->write('}');
  }

  protected function emitDo($do) {
    $this->out->write('do');
    $this->out->write('{');
    $this->emit($do->body);
    $this->out->write('} while (');
    $this->emit($do->expression);
    $this->out->write(');');
  }

  protected function emitWhile($while) {
    $this->out->write('while (');
    $this->emit($while->expression);
    $this->out->write(') {');
    $this->emit($while->body);
    $this->out->write('}');
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

  protected function emitArguments($arguments) {
    $s= sizeof($arguments) - 1;
    foreach ($arguments as $i => $argument) {
      $this->emit($argument);
      if ($i < $s) $this->out->write(', ');
    }
  }

  protected function emitNew($new) {
    $this->out->write('new '.$new->type.'(');
    $this->emitArguments($new->arguments);
    $this->out->write(')');
  }

  protected function emitNewClass($new) {
    $this->out->write('new class(');
    $this->emitArguments($new->arguments);
    $this->out->write(')');

    $new->definition->parent && $this->out->write(' extends '.$new->definition->parent);
    $new->definition->implements && $this->out->write(' implements '.implode(', ', $new->definition->implements));
    $this->out->write('{');
    foreach ($new->definition->body as $member) {
      $this->emit($member);
      $this->out->write("\n");
    }
    $this->out->write('}');
  }

  protected function emitInvoke($invoke) {
    $this->emit($invoke->expression);
    $this->out->write('(');
    $this->emitArguments($invoke->arguments);
    $this->out->write(')');
  }

  protected function emitScope($scope) {
    $this->out->write($scope->type.'::');
    $this->emit($scope->member);
  }

  protected function emitInstance($instance) {
    if ('new' === $instance->expression->kind) {
      $this->out->write('(');
      $this->emit($instance->expression);
      $this->out->write(')->');
    } else {
      $this->emit($instance->expression);
      $this->out->write('->');
    }

    if ('name' === $instance->member->kind) {
      $this->out->write($instance->member->value);
    } else {
      $this->out->write('{');
      $this->emit($instance->member);
      $this->out->write('}');
    }
  }

  protected function emitNullSafeInstance($instance) {
    $t= $this->temp();
    $this->out->write('null === ('.$t.'= ');
    $this->emit($instance->expression);
    $this->out->write(') ? null : '.$t.'->');

    if ('name' === $instance->member->kind) {
      $this->out->write($instance->member->value);
    } else {
      $this->out->write('{');
      $this->emit($instance->member);
      $this->out->write('}');
    }
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

  protected function emitUsing($using) {
    $variables= [];
    foreach ($using->arguments as $expression) {
      switch ($expression->kind) {
        case 'variable': $variables[]= '$'.$expression->value; break;
        case 'assignment': $variables[]= '$'.$expression->value->variable->value; break;
        default: $temp= $this->temp(); $variables[]= $temp; $this->out->write($temp.'=');
      }
      $this->emit($expression);
      $this->out->write(';');
    }

    $this->out->write('try {');
    $this->emit($using->body);

    $this->out->write('} finally {');
    foreach ($variables as $variable) {
      $this->out->write('if ('.$variable.' instanceof \lang\Closeable) { '.$variable.'->close(); }');
      $this->out->write('else if ('.$variable.' instanceof \IDisposable) { '.$variable.'->__dispose(); }');
      $this->out->write('unset('.$variable.');');
    }
    $this->out->write('}');
  }

  public function emit($arg) {
    if ($arg instanceof Element) {
      if ($arg->line > $this->line) {
        $this->out->write(str_repeat("\n", $arg->line - $this->line));
        $this->line= $arg->line;
      }

      if (isset($this->transformations[$arg->kind])) {
        foreach ($this->transformations[$arg->kind]($arg) as $n) {
          $this->{'emit'.$n->kind}($n->value);
        }
      } else {
        $this->{'emit'.$arg->kind}($arg->value);
      }
    } else {
      foreach ($arg as $node) {
        $this->emit($node);
        isset($node->symbol->std) || $this->out->write(';');
      }
    }
  }
}