<?php namespace lang\ast;

use io\streams\MemoryOutputStream;
use io\streams\StringWriter;
use lang\IllegalArgumentException;
use lang\ast\nodes\Value;
use lang\reflect\Package;

abstract class Emitter {
  const PROPERTY = 0;
  const METHOD   = 1;

  protected $unsupported= [];
  private $emit= [];

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

  public function transform($kind, $function) {
    if (null === $function) {
      unset($this->emit[$kind]);
    } else {
      $this->emit[$kind]= function($result, $arg) use($function) {
        foreach ($function($arg) as $n) {
          $this->{'emit'.$n->kind}($result, $n->value);
        }
      };
    }
    return $this;
  }

  /**
   * Emit nodes of a certain kind using the given emitter function. Overwrites
   * any previous handling, including builtin behavior!
   *
   * @param  string $kind
   * @param  function(lang.ast.Result, lang.ast.Node): void $function
   * @return self
   */
  public function handle($kind, $function) {
    $this->emit[$kind]= $function->bindTo($this, self::class);
    return $this;
  }

  /**
   * Collects emitted code into a buffer and returns it
   *
   * @param  lang.ast.Result
   * @param  function(): void $callable
   * @return string
   */
  protected function buffer($result, $callable) {
    $o= $result->out;
    $buffer= new MemoryOutputStream();
    $result->out= new StringWriter($buffer);

    try {
      $callable();
      return $buffer->getBytes();
    } finally {
      $result->out= $o;
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

  protected function paramType($type) {
    return $this->type($type->literal());
  }

  protected function returnType($type) {
    return $this->type($type->literal());
  }

  // See https://wiki.php.net/rfc/typed_properties_v2#supported_types
  protected function propertyType($type) {
    if (null === $type || $type instanceof UnionType || $type instanceof FunctionType) {
      return '';
    } else if ($type instanceof ArrayType || $type instanceof MapType) {
      return 'array';
    } else if ($type instanceof Type && 'callable' !== $type->literal() && 'void' !== $type->literal()) {
      return $type->literal();
    } else {
      return '';
    }
  }

  protected function emitStart($result, $start) {
    $result->out->write('<?php ');
  }

  protected function emitPackage($result, $package) {
    $result->out->write('namespace '.$package.";\n");
  }

  protected function emitImport($result, $import) {
    foreach ($import as $type => $alias) {
      $result->out->write('use '.$type.($alias ? ' as '.$alias : '').';');
    }
  }

  protected function emitImportConst($result, $import) {
    foreach ($import as $type => $alias) {
      $result->out->write('use const '.$type.($alias ? ' as '.$alias : '').';');
    }
  }

  protected function emitImportFunction($result, $import) {
    foreach ($import as $type => $alias) {
      $result->out->write('use function '.$type.($alias ? ' as '.$alias : '').';');
    }
  }

  protected function emitAnnotation($result, $annotations) {
    // NOOP
  }

  protected function emitCode($result, $code) {
    $result->out->write($code);
  }

  protected function emitLiteral($result, $literal) {
    $result->out->write($literal);
  }

  protected function emitName($result, $name) {
    $result->out->write($name);
  }

  protected function emitEcho($result, $echo) {
    $result->out->write('echo ');
    $s= sizeof($echo) - 1;
    foreach ($echo as $i => $expr) {
      $this->emit($result, $expr);
      if ($i < $s) $result->out->write(',');
    }
  }

  protected function emitBlock($result, $block) {
    $result->out->write('{');
    $this->emit($result, $block);
    $result->out->write('}');
  }

  protected function emitStatic($result, $static) {
    foreach ($static as $variable => $initial) {
      $result->out->write('static $'.$variable);
      if ($initial) {
        $result->out->write('=');
        $this->emit($result, $initial);
      }
      $result->out->write(';');
    }
  }

  protected function emitVariable($result, $variable) {
    $result->out->write('$'.$variable);
  }

  protected function emitCast($result, $cast) {
    static $native= ['string' => true, 'int' => true, 'float' => true, 'bool' => true, 'array' => true, 'object' => true];

    $name= $cast->type->name();
    if ('?' === $name{0}) {
      $result->out->write('cast(');
      $this->emit($result, $cast->expression);
      $result->out->write(',\''.$name.'\', false)');
    } else if (isset($native[$name])) {
      $result->out->write('('.$cast->type->literal().')');
      $this->emit($result, $cast->expression);
    } else {
      $result->out->write('cast(');
      $this->emit($result, $cast->expression);
      $result->out->write(',\''.$name.'\')');
    }
  }

  protected function emitArray($result, $array) {
    if (empty($array)) {
      $result->out->write('[]');
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
      $result->out->write('array_merge([');
      foreach ($array as $pair) {
        if ($pair[0]) {
          $this->emit($result, $pair[0]);
          $result->out->write('=>');
        }
        if ('unpack' === $pair[1]->kind) {
          if ('array' === $pair[1]->value->kind) {
            $result->out->write('],');
            $this->emit($result, $pair[1]->value);
            $result->out->write(',[');
          } else {
            $t= $result->temp();
            $result->out->write('],('.$t.'=');
            $this->emit($result, $pair[1]->value);
            $result->out->write(') instanceof \Traversable ? iterator_to_array('.$t.') : '.$t.',[');
          }
        } else {
          $this->emit($result, $pair[1]);
          $result->out->write(',');
        }
      }
      $result->out->write('])');
    } else {
      $result->out->write('[');
      foreach ($array as $pair) {
        if ($pair[0]) {
          $this->emit($result, $pair[0]);
          $result->out->write('=>');
        }
        $this->emit($result, $pair[1]);
        $result->out->write(',');
      }
      $result->out->write(']');
    }
  }

  protected function emitParameter($result, $parameter) {
    if ($parameter->type && $t= $this->paramType($parameter->type)) {
      $result->out->write($t.' ');
    }
    if ($parameter->variadic) {
      $result->out->write('... $'.$parameter->name);
    } else {
      $result->out->write(($parameter->reference ? '&' : '').'$'.$parameter->name);
    }
    if ($parameter->default) {
      $result->out->write('=');
      $this->emit($result, $parameter->default);
    }
    $result->locals[$parameter->name]= true;
  }

  protected function emitSignature($result, $signature) {
    $result->out->write('(');
    $s= sizeof($signature->parameters) - 1;
    foreach ($signature->parameters as $i => $parameter) {
      $this->emitParameter($result, $parameter);
      if ($i < $s) $result->out->write(', ');
    }
    $result->out->write(')');

    if ($signature->returns && $t= $this->returnType($signature->returns)) {
      $result->out->write(':'.$t);
    }
  }

  protected function emitFunction($result, $function) {
    $result->stack[]= $result->locals;
    $result->locals= [];

    $result->out->write('function '.$function->name); 
    $this->emitSignature($result, $function->signature);

    $result->out->write('{');
    $this->emit($result, $function->body);
    $result->out->write('}');

    $result->locals= array_pop($result->stack);
  }

  protected function emitClosure($result, $closure) {
    $result->stack[]= $result->locals;
    $result->locals= [];

    $result->out->write('function'); 
    $this->emitSignature($result, $closure->signature);

    if ($closure->use) {
      $result->out->write(' use('.implode(',', $closure->use).') ');
      foreach ($closure->use as $variable) {
        $result->locals[substr($variable, 1)]= true;
      }
    }
    $result->out->write('{');
    $this->emit($result, $closure->body);
    $result->out->write('}');

    $result->locals= array_pop($result->stack);
  }

  protected function emitLambda($result, $lambda) {
    $result->out->write('fn');
    $this->emitSignature($result, $lambda->signature);
    $result->out->write('=>');

    if (is_array($lambda->body)) {
      $result->out->write('{');
      $this->emit($result, $lambda->body);
      $result->out->write('}');
    } else {
      $this->emit($result, $lambda->body);
    }
  }

  protected function emitClass($result, $class) {
    array_unshift($result->meta, []);

    $result->out->write(implode(' ', $class->modifiers).' class '.$this->declaration($class->name));
    $class->parent && $result->out->write(' extends '.$class->parent);
    $class->implements && $result->out->write(' implements '.implode(', ', $class->implements));
    $result->out->write('{');
    foreach ($class->body as $member) {
      $this->emit($result, $member);
    }

    $result->out->write('static function __init() {');
    $this->emitMeta($result, $class->name, $class->annotations, $class->comment);
    $result->out->write('}} '.$class->name.'::__init();');
  }

  protected function emitAnnotations($result, $annotations) {
    foreach ($annotations as $name => $annotation) {
      $result->out->write("'".$name."' => ");
      if ($annotation) {
        $this->emit($result, $annotation);
        $result->out->write(',');
      } else {
        $result->out->write('null,');
      }
    }
  }

  protected function emitMeta($result, $name, $annotations, $comment) {
    $result->out->write('\xp::$meta[\''.strtr(ltrim($name, '\\'), '\\', '.').'\']= [');
    $result->out->write('"class" => [DETAIL_ANNOTATIONS => [');
    $this->emitAnnotations($result, $annotations);
    $result->out->write('], DETAIL_COMMENT => \''.str_replace("'", "\\'", $comment).'\'],');

    foreach (array_shift($result->meta) as $type => $lookup) {
      $result->out->write($type.' => [');
      foreach ($lookup as $key => $meta) {
        $result->out->write("'".$key."' => [DETAIL_ANNOTATIONS => [");
        $this->emitAnnotations($result, $meta[DETAIL_ANNOTATIONS]);
        $result->out->write('], DETAIL_TARGET_ANNO => [');
        foreach ($meta[DETAIL_TARGET_ANNO] as $target => $annotations) {
          $result->out->write("'$".$target."' => [");
          $this->emitAnnotations($result, $annotations);
          $result->out->write('],');
        }
        $result->out->write('], DETAIL_RETURNS => \''.$meta[DETAIL_RETURNS].'\'');
        $result->out->write(', DETAIL_COMMENT => \''.str_replace("'", "\\'", $meta[DETAIL_COMMENT]).'\'');
        $result->out->write(', DETAIL_ARGUMENTS => [\''.implode('\', \'', $meta[DETAIL_ARGUMENTS]).'\']],');
      }
      $result->out->write('],');
    }
    $result->out->write('];');
  }

  protected function emitInterface($result, $interface) {
    array_unshift($result->meta, []);

    $result->out->write('interface '.$this->declaration($interface->name));
    $interface->parents && $result->out->write(' extends '.implode(', ', $interface->parents));
    $result->out->write('{');
    foreach ($interface->body as $member) {
      $this->emit($result, $member);
      $result->out->write("\n");
    }
    $result->out->write('}');

    $this->emitMeta($result, $interface->name, $interface->annotations, $interface->comment);
  }

  protected function emitTrait($result, $trait) {
    array_unshift($result->meta, []);

    $result->out->write('trait '.$this->declaration($trait->name));
    $result->out->write('{');
    foreach ($trait->body as $member) {
      $this->emit($result, $member);
      $result->out->write("\n");
    }

    $result->out->write('static function __init() {');
    $this->emitMeta($result, $trait->name, $trait->annotations, $trait->comment);
    $result->out->write('}} '.$trait->name.'::__init();');
  }

  protected function emitUse($result, $use) {
    $result->out->write('use '.implode(',', $use->types));
    if ($use->aliases) {
      $result->out->write('{');
      foreach ($use->aliases as $reference => $alias) {
        $result->out->write($reference.' as '.$alias.';');
      }
      $result->out->write('}');
    } else {
      $result->out->write(';');
    }
  }

  protected function emitConst($result, $const) {
    $result->out->write(implode(' ', $const->modifiers).' const '.$const->name.'=');
    $this->emit($result, $const->expression);
    $result->out->write(';');
  }

  protected function emitProperty($result, $property) {
    $result->meta[0][self::PROPERTY][$property->name]= [
      DETAIL_RETURNS     => $property->type ? $property->type->name() : 'var',
      DETAIL_ANNOTATIONS => $property->annotations ? $property->annotations : [],
      DETAIL_COMMENT     => $property->comment,
      DETAIL_TARGET_ANNO => [],
      DETAIL_ARGUMENTS   => []
    ];

    $result->out->write(implode(' ', $property->modifiers).' '.$this->propertyType($property->type).' $'.$property->name);
    if (isset($property->expression)) {
      $result->out->write('=');
      $this->emit($result, $property->expression);
    }
    $result->out->write(';');
  }

  protected function emitMethod($result, $method) {
    $result->stack[]= $result->locals;
    $result->locals= ['this' => true];
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
        $result->meta[0][self::PROPERTY][$param->name]= [
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
    $result->out->write($declare);
    $result->out->write(implode(' ', $method->modifiers).' function '.$method->name);
    $this->emitSignature($result, $method->signature);

    if (null === $method->body) {
      $result->out->write(';');
    } else {
      $result->out->write(' {'.$promote);
      $this->emit($result, $method->body);
      $result->out->write('}');
    }

    $result->meta[0][self::METHOD][$method->name]= $meta;
    $result->locals= array_pop($result->stack);
  }

  protected function emitBraced($result, $braced) {
    $result->out->write('(');
    $this->emit($result, $braced);
    $result->out->write(')');
  }

  protected function emitBinary($result, $binary) {
    $this->emit($result, $binary->left);
    $result->out->write(' '.$binary->operator.' ');
    $this->emit($result, $binary->right);
  }

  protected function emitUnary($result, $unary) {
    $result->out->write($unary->operator);
    $this->emit($result, $unary->expression);
  }

  protected function emitTernary($result, $ternary) {
    $this->emit($result, $ternary->condition);
    $result->out->write('?');
    $this->emit($result, $ternary->expression);
    $result->out->write(':');
    $this->emit($result, $ternary->otherwise);
  }

  protected function emitOffset($result, $offset) {
    $this->emit($result, $offset->expression);
    if (null === $offset->offset) {
      $result->out->write('[]');
    } else {
      $result->out->write('[');
      $this->emit($result, $offset->offset);
      $result->out->write(']');
    }
  }

  protected function emitAssign($result, $target) {
    if ('variable' === $target->kind) {
      $result->out->write('$'.$target->value);
      $result->locals[$target->value]= true;
    } else if ('array' === $target->kind) {
      $result->out->write('list(');
      foreach ($target->value as $pair) {
        $this->emitAssign($result, $pair[1]);
        $result->out->write(',');
      }
      $result->out->write(')');
    } else {
      $this->emit($result, $target);
    }
  }

  protected function emitAssignment($result, $assignment) {
    $this->emitAssign($result, $assignment->variable);
    $result->out->write($assignment->operator);
    $this->emit($result, $assignment->expression);
  }

  protected function emitReturn($result, $return) {
    $result->out->write('return ');
    $return && $this->emit($result, $return);
    $result->out->write(';');
  }

  protected function emitIf($result, $if) {
    $result->out->write('if (');
    $this->emit($result, $if->expression);
    $result->out->write(') {');
    $this->emit($result, $if->body);
    $result->out->write('}');

    if ($if->otherwise) {
      $result->out->write('else {');
      $this->emit($result, $if->otherwise);
      $result->out->write('}');
    }
  }

  protected function emitSwitch($result, $switch) {
    $result->out->write('switch (');
    $this->emit($result, $switch->expression);
    $result->out->write(') {');
    foreach ($switch->cases as $case) {
      if ($case->expression) {
        $result->out->write('case ');
        $this->emit($result, $case->expression);
        $result->out->write(':');
      } else {
        $result->out->write('default:');
      }
      $this->emit($result, $case->body);
    }
    $result->out->write('}');
  }

  protected function emitCatch($result, $catch) {
    if (empty($catch->types)) {
      $result->out->write('catch(\\Throwable $'.$catch->variable.') {');
    } else {
      $result->out->write('catch('.implode('|', $catch->types).' $'.$catch->variable.') {');
    }
    $this->emit($result, $catch->body);
    $result->out->write('}');
  }

  protected function emitTry($result, $try) {
    $result->out->write('try {');
    $this->emit($result, $try->body);
    $result->out->write('}');
    if (isset($try->catches)) {
      foreach ($try->catches as $catch) {
        $this->emitCatch($result, $catch);
      }
    }
    if (isset($try->finally)) {
      $result->out->write('finally {');
      $this->emit($result, $try->finally);
      $result->out->write('}');
    }
  }

  protected function emitThrow($result, $throw) {
    $result->out->write('throw ');
    $this->emit($result, $throw);
    $result->out->write(';');
  }

  protected function emitThrowExpression($result, $throw) {
    $capture= [];
    foreach ($this->search($throw, 'variable') as $var) {
      if (isset($result->locals[$var->value])) {
        $capture[$var->value]= true;
      }
    }
    unset($capture['this']);

    $result->out->write('(function()');
    $capture && $result->out->write(' use($'.implode(', $', array_keys($capture)).')');
    $result->out->write('{ throw ');
    $this->emit($result, $throw);
    $result->out->write('; })()');
  }

  protected function emitForeach($result, $foreach) {
    $result->out->write('foreach (');
    $this->emit($result, $foreach->expression);
    $result->out->write(' as ');
    if ($foreach->key) {
      $this->emit($result, $foreach->key);
      $result->out->write(' => ');
    }
    $this->emit($result, $foreach->value);
    $result->out->write(') {');
    $this->emit($result, $foreach->body);
    $result->out->write('}');
  }

  protected function emitFor($result, $for) {
    $result->out->write('for (');
    $this->emitArguments($result, $for->initialization);
    $result->out->write(';');
    $this->emitArguments($result, $for->condition);
    $result->out->write(';');
    $this->emitArguments($result, $for->loop);
    $result->out->write(') {');
    $this->emit($result, $for->body);
    $result->out->write('}');
  }

  protected function emitDo($result, $do) {
    $result->out->write('do');
    $result->out->write('{');
    $this->emit($result, $do->body);
    $result->out->write('} while (');
    $this->emit($result, $do->expression);
    $result->out->write(');');
  }

  protected function emitWhile($result, $while) {
    $result->out->write('while (');
    $this->emit($result, $while->expression);
    $result->out->write(') {');
    $this->emit($result, $while->body);
    $result->out->write('}');
  }

  protected function emitBreak($result, $break) {
    $result->out->write('break ');
    $break && $this->emit($result, $break);
    $result->out->write(';');
  }

  protected function emitContinue($result, $continue) {
    $result->out->write('continue ');
    $continue && $this->emit($result, $continue);
    $result->out->write(';');
  }

  protected function emitLabel($result, $label) {
    $result->out->write($label.':');
  }

  protected function emitGoto($result, $goto) {
    $result->out->write('goto '.$goto);
  }

  protected function emitInstanceOf($result, $instanceof) {
    $this->emit($result, $instanceof->expression);
    $result->out->write(' instanceof ');
    if ($instanceof->type instanceof Node) {
      $this->emit($result, $instanceof->type);
    } else {
      $result->out->write($instanceof->type);
    }
  }

  protected function emitArguments($result, $arguments) {
    $s= sizeof($arguments) - 1;
    foreach ($arguments as $i => $argument) {
      $this->emit($result, $argument);
      if ($i < $s) $result->out->write(', ');
    }
  }

  protected function emitNew($result, $new) {
    $result->out->write('new '.$new->type.'(');
    $this->emitArguments($result, $new->arguments);
    $result->out->write(')');
  }

  protected function emitNewClass($result, $new) {
    $result->out->write('new class(');
    $this->emitArguments($result, $new->arguments);
    $result->out->write(')');

    $new->definition->parent && $result->out->write(' extends '.$new->definition->parent);
    $new->definition->implements && $result->out->write(' implements '.implode(', ', $new->definition->implements));
    $result->out->write('{');
    foreach ($new->definition->body as $member) {
      $this->emit($result, $member);
      $result->out->write("\n");
    }
    $result->out->write('}');
  }

  protected function emitInvoke($result, $invoke) {
    $this->emit($result, $invoke->expression);
    $result->out->write('(');
    $this->emitArguments($result, $invoke->arguments);
    $result->out->write(')');
  }

  protected function emitScope($result, $scope) {
    $result->out->write($scope->type.'::');
    $this->emit($result, $scope->member);
  }

  protected function emitInstance($result, $instance) {
    if ('new' === $instance->expression->kind) {
      $result->out->write('(');
      $this->emit($result, $instance->expression);
      $result->out->write(')->');
    } else {
      $this->emit($result, $instance->expression);
      $result->out->write('->');
    }

    if ('name' === $instance->member->kind) {
      $result->out->write($instance->member->value);
    } else {
      $result->out->write('{');
      $this->emit($result, $instance->member);
      $result->out->write('}');
    }
  }

  protected function emitUnpack($result, $unpack) {
    $result->out->write('...');
    $this->emit($result, $unpack);
  }

  protected function emitYield($result, $yield) {
    $result->out->write('yield ');
    if ($yield->key) {
      $this->emit($result, $yield->key);
      $result->out->write('=>');
    }
    if ($yield->value) {
      $this->emit($result, $yield->value);
    }
  }

  protected function emitFrom($result, $from) {
    $result->out->write('yield from ');
    $this->emit($result, $from);
  }

  public function emit($result, $arg) {
    if ($arg instanceof Element) {
      if ($arg->line > $result->line) {
        $result->out->write(str_repeat("\n", $arg->line - $result->line));
        $result->line= $arg->line;
      }

      if (isset($this->emit[$arg->kind])) {
        $this->emit[$arg->kind]($result, $arg);
      } else {
        $this->{'emit'.$arg->kind}($result, $arg->value);
      }
    } else {
      foreach ($arg as $node) {
        $this->emit($result, $node);
        isset($node->symbol->std) || $result->out->write(';');
      }
    }
  }
}