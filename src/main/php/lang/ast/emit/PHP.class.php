<?php namespace lang\ast\emit;

use lang\ast\nodes\{InstanceExpression, ScopeExpression, Variable};
use lang\ast\types\{IsUnion, IsFunction, IsArray, IsMap};
use lang\ast\{Emitter, Node, Type};

abstract class PHP extends Emitter {
  const PROPERTY = 0;
  const METHOD   = 1;

  protected $unsupported= [];

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
      '?' === $name[0] ||                     // nullable
      0 === strncmp($name, 'function', 8) ||  // function
      strstr($name, '|') ||                   // union
      isset($this->unsupported[$name])
    ) ? null : $name;
  }

  protected function paramType($type) {
    return $this->type($type->literal());
  }

  protected function returnType($type) {
    return $this->type($type->literal());
  }

  // See https://wiki.php.net/rfc/typed_properties_v2#supported_types
  protected function propertyType($type) {
    if (null === $type || $type instanceof IsUnion || $type instanceof IsFunction) {
      return '';
    } else if ($type instanceof IsArray || $type instanceof IsMap) {
      return 'array';
    } else if ('callable' === $type->literal() || 'void' === $type->literal()) {
      return '';
    } else {
      return $type->literal();
    }
  }

  protected function emitStart($result, $start) {
    // NOOP
  }

  protected function emitNamespace($result, $declaration) {
    $result->out->write('namespace '.$declaration->name);
  }

  protected function emitImport($result, $import) {
    foreach ($import->names as $name => $alias) {
      $result->out->write('use '.$import->type.' '.$name.($alias ? ' as '.$alias : '').';');
    }
  }

  protected function emitOperator($result, $operator) {
    // NOOP
  }

  protected function emitName($result, $name) {
    // NOOP
  }

  protected function emitCode($result, $code) {
    $result->out->write($code->value);
  }

  protected function emitLiteral($result, $literal) {
    $result->out->write($literal->expression);
  }

  protected function emitEcho($result, $echo) {
    $result->out->write('echo ');
    $s= sizeof($echo->expressions) - 1;
    foreach ($echo->expressions as $i => $expr) {
      $this->emitOne($result, $expr);
      if ($i < $s) $result->out->write(',');
    }
  }

  protected function emitBlock($result, $block) {
    $result->out->write('{');
    $this->emitAll($result, $block->statements);
    $result->out->write('}');
  }

  protected function emitStatic($result, $static) {
    foreach ($static->initializations as $variable => $initial) {
      $result->out->write('static $'.$variable);
      if ($initial) {
        $result->out->write('=');
        $this->emitOne($result, $initial);
      }
      $result->out->write(';');
    }
  }

  protected function emitVariable($result, $variable) {
    $result->out->write('$'.$variable->name);
  }

  protected function emitCast($result, $cast) {
    static $native= ['string' => true, 'int' => true, 'float' => true, 'bool' => true, 'array' => true, 'object' => true];

    $name= $cast->type->name();
    if ('?' === $name[0]) {
      $result->out->write('cast(');
      $this->emitOne($result, $cast->expression);
      $result->out->write(',\''.$name.'\', false)');
    } else if (isset($native[$name])) {
      $result->out->write('('.$cast->type->literal().')');
      $this->emitOne($result, $cast->expression);
    } else {
      $result->out->write('cast(');
      $this->emitOne($result, $cast->expression);
      $result->out->write(',\''.$name.'\')');
    }
  }

  protected function emitArray($result, $array) {
    if (empty($array->values)) {
      $result->out->write('[]');
      return;
    }

    $unpack= false;
    foreach ($array->values as $pair) {
      if ('unpack' === $pair[1]->kind) {
        $unpack= true;
        break;
      }
    }

    if ($unpack) {
      $result->out->write('array_merge([');
      foreach ($array->values as $pair) {
        if ($pair[0]) {
          $this->emitOne($result, $pair[0]);
          $result->out->write('=>');
        }
        if ('unpack' === $pair[1]->kind) {
          if ('array' === $pair[1]->expression->kind) {
            $result->out->write('],');
            $this->emitOne($result, $pair[1]->expression);
            $result->out->write(',[');
          } else {
            $t= $result->temp();
            $result->out->write('],('.$t.'=');
            $this->emitOne($result, $pair[1]->expression);
            $result->out->write(') instanceof \Traversable ? iterator_to_array('.$t.') : '.$t.',[');
          }
        } else {
          $this->emitOne($result, $pair[1]);
          $result->out->write(',');
        }
      }
      $result->out->write('])');
    } else {
      $result->out->write('[');
      foreach ($array->values as $pair) {
        if ($pair[0]) {
          $this->emitOne($result, $pair[0]);
          $result->out->write('=>');
        }
        $this->emitOne($result, $pair[1]);
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
      $this->emitOne($result, $parameter->default);
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
    $this->emitAll($result, $function->body);
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
    $this->emitAll($result, $closure->body);
    $result->out->write('}');

    $result->locals= array_pop($result->stack);
  }

  protected function emitLambda($result, $lambda) {
    $result->out->write('fn');
    $this->emitSignature($result, $lambda->signature);
    $result->out->write('=>');

    if (is_array($lambda->body)) {
      $result->out->write('{');
      $this->emitAll($result, $lambda->body);
      $result->out->write('}');
    } else {
      $this->emitOne($result, $lambda->body);
    }
  }

  protected function emitClass($result, $class) {
    array_unshift($result->meta, []);

    $result->out->write(implode(' ', $class->modifiers).' class '.$this->declaration($class->name));
    $class->parent && $result->out->write(' extends '.$class->parent);
    $class->implements && $result->out->write(' implements '.implode(', ', $class->implements));
    $result->out->write('{');
    foreach ($class->body as $member) {
      $this->emitOne($result, $member);
    }

    $result->out->write('static function __init() {');
    $this->emitMeta($result, $class->name, $class->annotations, $class->comment);
    $result->out->write('}} '.$class->name.'::__init();');
  }

  /** Stores lowercased, unnamespaced name in annotations for BC reasons! */
  protected function annotations($result, $annotations) {
    $lookup= [];
    foreach ($annotations as $name => $arguments) {
      $p= strrpos($name, '\\');
      $key= lcfirst(false === $p ? $name : substr($name, $p + 1));
      $result->out->write("'".$key."' => ");
      $name === $key || $lookup[$key]= $name;

      if (empty($arguments)) {
        $result->out->write('null,');
      } else if (1 === sizeof($arguments) && isset($arguments[0])) {
        $this->emitOne($result, $arguments[0]);
        $result->out->write(',');
      } else {
        $result->out->write('[');
        foreach ($arguments as $name => $argument) {
          is_string($name) && $result->out->write("'".$name."' => ");
          $this->emitOne($result, $argument);
          $result->out->write(',');
        }
        $result->out->write('],');
      }
    }
    return $lookup;
  }

  /** Emits annotations in XP format - and mappings for their names */
  protected function attributes($result, $annotations, $target) {
    $result->out->write('DETAIL_ANNOTATIONS => [');
    $lookup= $this->annotations($result, $annotations);
    $result->out->write('], DETAIL_TARGET_ANNO => [');
    foreach ($target as $name => $annotations) {
      $result->out->write("'$".$name."' => [");
      foreach ($this->annotations($result, $annotations) as $key => $value) {
        $lookup[$key]= $value;
      }
      $result->out->write('],');
    }
    foreach ($lookup as $key => $value) {
      $result->out->write("'".$key."' => '".$value."',");
    }
    $result->out->write(']');
  }

  /** Removes leading, intermediate and trailing stars from apidoc comments */
  private function comment($comment) {
    if (0 === strlen($comment)) {
      return 'null';
    } else if ('/' === $comment[0]) {
      return "'".str_replace("'", "\\'", trim(preg_replace('/\n\s+\* ?/', "\n", substr($comment, 3, -2))))."'";
    } else {
      return "'".str_replace("'", "\\'", $comment)."'";
    }
  }

  /** Emit meta information so that the reflection API won't have to parse it */
  protected function emitMeta($result, $name, $annotations, $comment) {
    if (null === $name) {
      $result->out->write('\xp::$meta[strtr(self::class, "\\\\", ".")]= [');
    } else {
      $result->out->write('\xp::$meta[\''.strtr(ltrim($name, '\\'), '\\', '.').'\']= [');
    }
    $result->out->write('"class" => [');
    $this->attributes($result, $annotations, []);
    $result->out->write(', DETAIL_COMMENT => '.$this->comment($comment).'],');

    foreach (array_shift($result->meta) as $type => $lookup) {
      $result->out->write($type.' => [');
      foreach ($lookup as $key => $meta) {
        $result->out->write("'".$key."' => [");
        $this->attributes($result, $meta[DETAIL_ANNOTATIONS], $meta[DETAIL_TARGET_ANNO]);
        $result->out->write(', DETAIL_RETURNS => \''.$meta[DETAIL_RETURNS].'\'');
        $result->out->write(', DETAIL_COMMENT => '.$this->comment($meta[DETAIL_COMMENT]));
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
      $this->emitOne($result, $member);
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
      $this->emitOne($result, $member);
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
        $result->out->write($reference.' '.key($alias).' '.current($alias).';');
      }
      $result->out->write('}');
    } else {
      $result->out->write(';');
    }
  }

  protected function emitConst($result, $const) {
    $result->out->write(implode(' ', $const->modifiers).' const '.$const->name.'=');
    $this->emitOne($result, $const->expression);
    $result->out->write(';');
  }

  protected function emitProperty($result, $property) {
    $result->meta[0][self::PROPERTY][$property->name]= [
      DETAIL_RETURNS     => $property->type ? $property->type->name() : 'var',
      DETAIL_ANNOTATIONS => $property->annotations,
      DETAIL_COMMENT     => $property->comment,
      DETAIL_TARGET_ANNO => [],
      DETAIL_ARGUMENTS   => []
    ];

    $result->out->write(implode(' ', $property->modifiers).' '.$this->propertyType($property->type).' $'.$property->name);
    if (isset($property->expression)) {
      $result->out->write('=');
      $this->emitOne($result, $property->expression);
    }
    $result->out->write(';');
  }

  protected function emitMethod($result, $method) {
    $result->stack[]= $result->locals;
    $result->locals= ['this' => true];
    $meta= [
      DETAIL_RETURNS     => $method->signature->returns ? $method->signature->returns->name() : 'var',
      DETAIL_ANNOTATIONS => $method->annotations,
      DETAIL_COMMENT     => $method->comment,
      DETAIL_TARGET_ANNO => [],
      DETAIL_ARGUMENTS   => []
    ];

    $declare= $promote= $params= '';
    foreach ($method->signature->parameters as $param) {
      if (isset($param->promote)) {
        $declare.= $param->promote.' $'.$param->name.';';
        $promote.= '$this->'.$param->name.'= '.($param->reference ? '&$' : '$').$param->name.';';
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
      $this->emitAll($result, $method->body);
      $result->out->write('}');
    }

    $result->meta[0][self::METHOD][$method->name]= $meta;
    $result->locals= array_pop($result->stack);
  }

  protected function emitBraced($result, $braced) {
    $result->out->write('(');
    $this->emitOne($result, $braced->expression);
    $result->out->write(')');
  }

  protected function emitBinary($result, $binary) {
    $this->emitOne($result, $binary->left);
    $result->out->write(' '.$binary->operator.' ');
    $this->emitOne($result, $binary->right);
  }

  protected function emitPrefix($result, $unary) {
    $result->out->write($unary->operator);
    $this->emitOne($result, $unary->expression);
  }

  protected function emitSuffix($result, $unary) {
    $this->emitOne($result, $unary->expression);
    $result->out->write($unary->operator);
  }

  protected function emitTernary($result, $ternary) {
    $this->emitOne($result, $ternary->condition);
    $result->out->write('?');
    $this->emitOne($result, $ternary->expression);
    $result->out->write(':');
    $this->emitOne($result, $ternary->otherwise);
  }

  protected function emitOffset($result, $offset) {
    $this->emitOne($result, $offset->expression);
    if (null === $offset->offset) {
      $result->out->write('[]');
    } else {
      $result->out->write('[');
      $this->emitOne($result, $offset->offset);
      $result->out->write(']');
    }
  }

  protected function emitAssign($result, $target) {
    if ('variable' === $target->kind) {
      $result->out->write('$'.$target->name);
      $result->locals[$target->name]= true;
    } else if ('array' === $target->kind) {
      $result->out->write('list(');
      foreach ($target->values as $pair) {
        $this->emitAssign($result, $pair[1]);
        $result->out->write(',');
      }
      $result->out->write(')');
    } else {
      $this->emitOne($result, $target);
    }
  }

  protected function emitAssignment($result, $assignment) {
    $this->emitAssign($result, $assignment->variable);
    $result->out->write($assignment->operator);
    $this->emitOne($result, $assignment->expression);
  }

  protected function emitReturn($result, $return) {
    $result->out->write('return ');
    $return->expression && $this->emitOne($result, $return->expression);
  }

  protected function emitIf($result, $if) {
    $result->out->write('if (');
    $this->emitOne($result, $if->expression);
    $result->out->write(') {');
    $this->emitAll($result, $if->body);
    $result->out->write('}');

    if ($if->otherwise) {
      $result->out->write('else {');
      $this->emitAll($result, $if->otherwise);
      $result->out->write('}');
    }
  }

  protected function emitSwitch($result, $switch) {
    $result->out->write('switch (');
    $this->emitOne($result, $switch->expression);
    $result->out->write(') {');
    foreach ($switch->cases as $case) {
      if ($case->expression) {
        $result->out->write('case ');
        $this->emitOne($result, $case->expression);
        $result->out->write(':');
      } else {
        $result->out->write('default:');
      }
      $this->emitAll($result, $case->body);
    }
    $result->out->write('}');
  }

  protected function emitMatch($result, $match) {
    $t= $result->temp();
    $result->out->write('('.$t.'=');
    $this->emitOne($result, $match->expression);
    $result->out->write(')');

    $b= 0;
    foreach ($match->cases as $case) {
      foreach ($case->expressions as $expression) {
        $b && $result->out->write($t);
        $result->out->write('===(');
        $this->emitOne($result, $expression);
        $result->out->write(')?');
        $this->emitOne($result, $case->body);
        $result->out->write(':(');
        $b++;
      }
    }

    // Emit IIFE for raising an error until we have throw expressions
    if (null === $match->default) {
      $result->out->write('function() use('.$t.') { throw new \\Error("Unhandled match value of type ".gettype('.$t.')); })(');
    } else {
      $this->emitOne($result, $match->default);
    }
    $result->out->write(str_repeat(')', $b));
  }

  protected function emitCatch($result, $catch) {
    if (empty($catch->types)) {
      $result->out->write('catch(\\Throwable $'.$catch->variable.') {');
    } else {
      $result->out->write('catch('.implode('|', $catch->types).' $'.$catch->variable.') {');
    }
    $this->emitAll($result, $catch->body);
    $result->out->write('}');
  }

  protected function emitTry($result, $try) {
    $result->out->write('try {');
    $this->emitAll($result, $try->body);
    $result->out->write('}');
    if (isset($try->catches)) {
      foreach ($try->catches as $catch) {
        $this->emitCatch($result, $catch);
      }
    }
    if (isset($try->finally)) {
      $result->out->write('finally {');
      $this->emitAll($result, $try->finally);
      $result->out->write('}');
    }
  }

  protected function emitThrow($result, $throw) {
    $result->out->write('throw ');
    $this->emitOne($result, $throw->expression);
    $result->out->write(';');
  }

  protected function emitThrowExpression($result, $throw) {
    $capture= [];
    foreach ($result->codegen->search($throw->expression, 'variable') as $var) {
      if (isset($result->locals[$var->name])) {
        $capture[$var->name]= true;
      }
    }
    unset($capture['this']);

    $result->out->write('(function()');
    $capture && $result->out->write(' use($'.implode(', $', array_keys($capture)).')');
    $result->out->write('{ throw ');
    $this->emitOne($result, $throw->expression);
    $result->out->write('; })()');
  }

  protected function emitForeach($result, $foreach) {
    $result->out->write('foreach (');
    $this->emitOne($result, $foreach->expression);
    $result->out->write(' as ');
    if ($foreach->key) {
      $this->emitOne($result, $foreach->key);
      $result->out->write(' => ');
    }
    $this->emitOne($result, $foreach->value);
    $result->out->write(') {');
    $this->emitAll($result, $foreach->body);
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
    $this->emitAll($result, $for->body);
    $result->out->write('}');
  }

  protected function emitDo($result, $do) {
    $result->out->write('do');
    $result->out->write('{');
    $this->emitAll($result, $do->body);
    $result->out->write('} while (');
    $this->emitOne($result, $do->expression);
    $result->out->write(');');
  }

  protected function emitWhile($result, $while) {
    $result->out->write('while (');
    $this->emitOne($result, $while->expression);
    $result->out->write(') {');
    $this->emitAll($result, $while->body);
    $result->out->write('}');
  }

  protected function emitBreak($result, $break) {
    $result->out->write('break ');
    $break->expression && $this->emitOne($result, $break->expression);
    $result->out->write(';');
  }

  protected function emitContinue($result, $continue) {
    $result->out->write('continue ');
    $continue->expression && $this->emitOne($result, $continue->expression);
    $result->out->write(';');
  }

  protected function emitLabel($result, $label) {
    $result->out->write($label->name.':');
  }

  protected function emitGoto($result, $goto) {
    $result->out->write('goto '.$goto->label);
  }

  protected function emitInstanceOf($result, $instanceof) {
    $type= $instanceof->type;

    // Supported: instanceof T, instanceof $t, instanceof $t->MEMBER; instanceof T::MEMBER
    // Unsupported: instanceof EXPR
    if ($type instanceof Variable || $type instanceof InstanceExpression || $type instanceof ScopeExpression) {
      $this->emitOne($result, $instanceof->expression);
      $result->out->write(' instanceof ');
      $this->emitOne($result, $type);
    } else if ($type instanceof Node) {
      $t= $result->temp();
      $result->out->write('('.$t.'= ');
      $this->emitOne($result, $type);
      $result->out->write(')?');
      $this->emitOne($result, $instanceof->expression);
      $result->out->write(' instanceof '.$t.':null');
    } else {
      $this->emitOne($result, $instanceof->expression);
      $result->out->write(' instanceof '.$type);
    }
  }

  protected function emitArguments($result, $arguments) {
    $s= sizeof($arguments) - 1;
    $i= 0;
    foreach ($arguments as $argument) {
      $this->emitOne($result, $argument);
      if ($i++ < $s) $result->out->write(', ');
    }
  }

  protected function emitNew($result, $new) {
    if ($new->type instanceof Node) {
      $t= $result->temp();
      $result->out->write('('.$t.'= ');
      $this->emitOne($result, $new->type);
      $result->out->write(') ? new '.$t.'(');
      $this->emitArguments($result, $new->arguments);
      $result->out->write(') : null');
    } else {
      $result->out->write('new '.$new->type.'(');
      $this->emitArguments($result, $new->arguments);
      $result->out->write(')');
    }
  }

  protected function emitNewClass($result, $new) {
    array_unshift($result->meta, []);

    $result->out->write('(new class(');
    $this->emitArguments($result, $new->arguments);
    $result->out->write(')');

    $new->definition->parent && $result->out->write(' extends '.$new->definition->parent);
    $new->definition->implements && $result->out->write(' implements '.implode(', ', $new->definition->implements));
    $result->out->write('{');

    foreach ($new->definition->body as $member) {
      $this->emitOne($result, $member);
      $result->out->write("\n");
    }
    $result->out->write('function __new() {');
    $this->emitMeta($result, null, [], null);
    $result->out->write('return $this; }})->__new()');
  }

  protected function emitInvoke($result, $invoke) {
    $this->emitOne($result, $invoke->expression);
    $result->out->write('(');
    $this->emitArguments($result, $invoke->arguments);
    $result->out->write(')');
  }

  protected function emitScope($result, $scope) {
    if ($scope->type instanceof Variable) {
      $this->emitOne($result, $scope->type);
      $result->out->write('::');
      $this->emitOne($result, $scope->member);
    } else if ($scope->type instanceof Node) {
      $t= $result->temp();
      $result->out->write('('.$t.'=');
      $this->emitOne($result, $scope->type);
      $result->out->write(')?'.$t.'::');
      $this->emitOne($result, $scope->member);
      $result->out->write(':null');
    } else {
      $result->out->write($scope->type.'::');
      $this->emitOne($result, $scope->member);
    }
  }

  protected function emitInstance($result, $instance) {
    if ('new' === $instance->expression->kind) {
      $result->out->write('(');
      $this->emitOne($result, $instance->expression);
      $result->out->write(')->');
    } else {
      $this->emitOne($result, $instance->expression);
      $result->out->write('->');
    }

    if ('literal' === $instance->member->kind) {
      $result->out->write($instance->member->expression);
    } else {
      $result->out->write('{');
      $this->emitOne($result, $instance->member);
      $result->out->write('}');
    }
  }

  protected function emitNullsafeInstance($result, $instance) {
    $t= $result->temp();
    $result->out->write('null===('.$t.'=');
    $this->emitOne($result, $instance->expression);
    $result->out->write(')?null:'.$t.'->');

    if ('literal' === $instance->member->kind) {
      $result->out->write($instance->member->expression);
    } else {
      $result->out->write('{');
      $this->emitOne($result, $instance->member);
      $result->out->write('}');
    }
  }

  protected function emitUnpack($result, $unpack) {
    $result->out->write('...');
    $this->emitOne($result, $unpack->expression);
  }

  protected function emitYield($result, $yield) {
    $result->out->write('yield ');
    if ($yield->key) {
      $this->emitOne($result, $yield->key);
      $result->out->write('=>');
    }
    if ($yield->value) {
      $this->emitOne($result, $yield->value);
    }
  }

  protected function emitFrom($result, $from) {
    $result->out->write('yield from ');
    $this->emitOne($result, $from->iterable);
  }
}