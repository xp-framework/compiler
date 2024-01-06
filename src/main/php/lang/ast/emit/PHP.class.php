<?php namespace lang\ast\emit;

use Override;
use lang\ast\emit\Escaping;
use lang\ast\nodes\{
  Annotation,
  ArrayLiteral,
  BinaryExpression,
  Block,
  Comment,
  Expression,
  InstanceExpression,
  Literal,
  Property,
  ScopeExpression,
  UnpackExpression,
  Variable
};
use lang\ast\types\{IsUnion, IsFunction, IsArray, IsMap, IsNullable, IsExpression};
use lang\ast\{Emitter, Node, Type, Result};

abstract class PHP extends Emitter {
  const PROPERTY = 0;
  const METHOD   = 1;
  const CONSTANT = 2;

  protected $literals= [];

  /**
   * Creates result
   *
   * @param  io.streams.OutputStream $target
   * @return lang.ast.Result
   */
  protected function result($target) {
    return new GeneratedCode($target, '<?php ');
  }

  /**
   * Emit type literal or NULL if no type should be emitted
   *
   * @param  ?lang.ast.Type $type
   * @return ?string
   */
  public function literal($type) {
    return null === $type ? null : $this->literals[get_class($type)]($type);
  }

  /**
   * Returns whether a given node is a constant expression:
   *
   * - Any literal
   * - Arrays where all members are literals
   * - Scope expressions with literal members (self::class, T::const)
   * - Binary expression where left- and right hand side are literals
   *
   * @see    https://wiki.php.net/rfc/const_scalar_exprs
   * @param  lang.ast.Result $result
   * @param  lang.ast.Node $node
   * @return bool
   */
  protected function isConstant($result, $node) {
    if ($node instanceof Literal) {
      return true;
    } else if ($node instanceof ArrayLiteral) {
      foreach ($node->values as $element) {
        if (!$this->isConstant($result, $element[1])) return false;
      }
      return true;
    } else if ($node instanceof ScopeExpression) {
      return (
        $node->member instanceof Literal &&
        is_string($node->type) &&
        !$result->codegen->lookup($node->type)->rewriteEnumCase($node->member->expression)
      );
    } else if ($node instanceof BinaryExpression) {
      return $this->isConstant($result, $node->left) && $this->isConstant($result, $node->right);
    }
    return false;
  }

  /**
   * As of PHP 7.4: Property type declarations support all type declarations
   * supported by PHP with the exception of void and callable.
   *
   * @see    https://wiki.php.net/rfc/typed_properties_v2#supported_types
   * @param  ?lang.ast.Type $type
   * @return ?string
   */
  protected function propertyType($type) {
    if (null === $type || $type instanceof IsFunction || 'callable' === $type->literal()) {
      return null;
    } else {
      return $this->literal($type);
    }
  }

  /**
   * As of PHP 8.3: Constant type declarations support all type declarations
   * supported by PHP with the exception of void and callable.
   *
   * @see    https://wiki.php.net/rfc/typed_class_constants#supported_types
   * @param  ?lang.ast.Type $type
   * @return ?string
   */
  protected function constantType($type) {
    if (null === $type || $type instanceof IsFunction || 'callable' === $type->literal()) {
      return null;
    } else {
      return $this->literal($type);
    }
  }

  /**
   * Enclose a node inside a closure
   *
   * @param  lang.ast.Result $result
   * @param  lang.ast.Node $node
   * @param  ?lang.ast.nodes.Signature $signature
   * @param  bool $static
   * @param  function(lang.ast.Result, lang.ast.Node): void $emit
   */
  protected function enclose($result, $node, $signature, $static, $emit) {
    $capture= [];
    foreach ($result->codegen->search($node, 'variable') as $var) {
      if (isset($result->locals[$var->pointer])) {
        $capture[$var->pointer]= true;
      }
    }
    unset($capture['this']);

    $locals= $result->locals;
    $result->locals= [];
    if ($signature) {
      $static ? $result->out->write('static function') : $result->out->write('function');
      $this->emitSignature($result, $signature);
      foreach ($signature->parameters as $param) {
        unset($capture[$param->name]);
      }
    } else {
      $result->out->write('function()');
    }

    if ($capture) {
      $result->out->write('use($'.implode(', $', array_keys($capture)).')');
      foreach ($capture as $name => $_) {
        $result->locals[$name]= true;
      }
    }

    $result->out->write('{');
    $emit($result, $node);
    $result->out->write('}');
    $result->locals= $locals;
  }

  /**
   * Emits local initializations
   *
   * @param  lang.ast.Result $result
   * @param  [:lang.ast.Node] $init
   * @return void
   */
  protected function emitInitializations($result, $init) {
    foreach ($init as $assign => $expression) {
      $result->out->write($assign.'=');
      $this->emitOne($result, $expression);
      $result->out->write(';');
    }
  }

  /**
   * Convert blocks to IIFEs to allow a list of statements where PHP syntactically
   * doesn't, e.g. `fn`-style lambdas or match expressions.
   *
   * @param  lang.ast.Result $result
   * @param  lang.ast.Node $expression
   * @return void
   */
  protected function emitAsExpression($result, $expression) {
    if ($expression instanceof Block) {
      $result->out->write('(');
      $this->enclose($result, $expression, null, false, function($result, $expression) {
        $this->emitAll($result, $expression->statements);
      });
      $result->out->write(')()');
    } else {
      $this->emitOne($result, $expression);
    }
  }

  protected function emitDirectives($result, $directives) {
    $result->out->write('declare(');
    foreach ($directives->declare as $directive => $value) {
      $result->out->write($directive.'=');
      $this->emitOne($result, $value);
    }
    $result->out->write(')');
  }

  protected function emitNamespace($result, $declaration) {
    $result->out->write('namespace '.$declaration->name);
  }

  protected function emitImport($result, $import) {
    foreach ($import->names as $name => $alias) {
      $result->out->write('use '.$import->type.' '.$name.($alias ? ' as '.$alias : '').';');
    }
  }

  protected function emitCode($result, $code) {
    $result->out->write($code->value);
  }

  protected function emitLiteral($result, $literal) {
    $result->out->write($literal->expression);
  }

  protected function emitEcho($result, $echo) {
    $result->out->write('echo ');
    foreach ($echo->expressions as $i => $expr) {
      if ($i++) $result->out->write(',');
      $this->emitOne($result, $expr);
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
    if ($variable->const) {
      $result->out->write('$'.$variable->pointer);
    } else {
      $result->out->write('$');
      $this->emitOne($result, $variable->pointer);
    }
  }

  protected function emitExpression($result, $expression) {
    $result->out->write('{');
    $this->emitOne($result, $expression->inline);
    $result->out->write('}');
  }

  protected function emitCast($result, $cast) {
    static $native= ['string' => true, 'int' => true, 'float' => true, 'bool' => true, 'array' => true, 'object' => true];

    // Inline nullable checks using ternaries
    if ($cast->type instanceof IsNullable) {
      $t= $result->temp();
      $result->out->write('null===('.$t.'=');
      $this->emitOne($result, $cast->expression);
      $result->out->write(')?null:');

      $name= $cast->type->element->name();
      $expression= new Variable(substr($t, 1));
    } else {
      $name= $cast->type->name();
      $expression= $cast->expression;
    }

    if (isset($native[$name])) {
      $result->out->write('('.$name.')');
      $this->emitOne($result, $expression);
    } else {
      $result->out->write('cast(');
      $this->emitOne($result, $expression);
      $result->out->write(',\''.$name.'\')');
    }
  }

  protected function emitArray($result, $array) {
    $result->out->write('[');
    foreach ($array->values as $pair) {
      if ($pair[0]) {
        $this->emitOne($result, $pair[0]);
        $result->out->write('=>');
      }
      $this->emitOne($result, $pair[1] ?? $this->raise('Cannot use empty array elements in arrays'));
      $result->out->write(',');
    }
    $result->out->write(']');
  }

  protected function emitParameter($result, $parameter) {
    $parameter->annotations && $this->emitOne($result, $parameter->annotations);
    if ($parameter->type && $t= $this->literal($parameter->type)) {
      $result->out->write($t.' ');
    }
    if ($parameter->variadic) {
      $result->out->write('... $'.$parameter->name);
    } else {
      $result->out->write(($parameter->reference ? '&' : '').'$'.$parameter->name);
    }
    if ($parameter->default) {
      if ($this->isConstant($result, $parameter->default)) {
        $result->out->write('=');
        $this->emitOne($result, $parameter->default);
      } else {
        $result->out->write('=null');
      }
    }
    $result->locals[$parameter->name]= true;
  }

  protected function emitSignature($result, $signature, $use= null) {
    $result->out->write('(');
    foreach ($signature->parameters as $i => $parameter) {
      if ($i++) $result->out->write(',');
      $this->emitParameter($result, $parameter);
    }
    $result->out->write(')');

    if ($use) {
      $result->out->write(' use('.implode(',', $use).') ');
      foreach ($use as $variable) {
        $result->locals[substr($variable, 1)]= true;
      }
    }

    if ($signature->returns && $t= $this->literal($signature->returns)) {
      $result->out->write(':'.$t);
    }
  }

  protected function emitFunction($result, $function) {
    $locals= $result->locals;
    $result->locals= [];

    $result->out->write('function '.($function->signature->byref ? '&' : '').$function->name);
    $this->emitSignature($result, $function->signature);

    $result->out->write('{');
    $this->emitAll($result, $function->body);
    $result->out->write('}');

    $result->locals= $locals;
  }

  protected function emitClosure($result, $closure) {
    $locals= $result->locals;
    $result->locals= [];

    $closure->static ? $result->out->write('static function') : $result->out->write('function');
    $this->emitSignature($result, $closure->signature, $closure->use);

    $result->out->write('{');
    $this->emitAll($result, $closure->body);
    $result->out->write('}');

    $result->locals= $locals;
  }

  protected function emitLambda($result, $lambda) {
    $locals= $result->locals;

    $lambda->static ? $result->out->write('static fn') : $result->out->write('fn');
    $this->emitSignature($result, $lambda->signature);
    $result->out->write('=>');
    $this->emitOne($result, $lambda->body);

    $result->locals= $locals;
  }

  protected function emitEnumCase($result, $case) {
    $result->codegen->scope[0]->meta[self::CONSTANT][$case->name]= [
      DETAIL_RETURNS     => 'self',
      DETAIL_ANNOTATIONS => $case->annotations,
      DETAIL_COMMENT     => $case->comment,
      DETAIL_TARGET_ANNO => [],
      DETAIL_ARGUMENTS   => []
    ];

    $case->annotations && $this->emitOne($result, $case->annotations);
    $result->out->write('case '.$case->name);
    if ($case->expression) {
      $result->out->write('=');
      $this->emitOne($result, $case->expression);
    }
    $result->out->write(';');
  }

  protected function emitEnum($result, $enum) {
    $context= $result->codegen->enter(new InType($enum));

    $enum->comment && $this->emitOne($result, $enum->comment);
    $enum->annotations && $this->emitOne($result, $enum->annotations);
    $result->at($enum->declared)->out->write('enum '.$enum->declaration());
    $enum->base && $result->out->write(':'.$enum->base);

    if ($enum->implements) {
      $list= '';
      foreach ($enum->implements as $type) {
        $list.= ', '.$type->literal();
      }
      $result->out->write(' implements '.substr($list, 2));
    }

    $result->out->write('{');
    foreach ($enum->body as $member) {
      $this->emitOne($result, $member);
    }
    $result->out->write('static function __init() {');
    $this->emitInitializations($result, $context->statics);
    $this->emitMeta($result, $enum->name, $enum->annotations, $enum->comment);
    $result->out->write('}} '.$enum->declaration().'::__init();');

    $result->codegen->leave();
  }

  protected function emitClass($result, $class) {
    $context= $result->codegen->scope[0] ?? $result->codegen->enter(new InType($class));

    $class->comment && $this->emitOne($result, $class->comment);
    $class->annotations && $this->emitOne($result, $class->annotations);
    $result->at($class->declared)->out->write(implode(' ', $class->modifiers).' class '.$class->declaration());
    $class->parent && $result->out->write(' extends '.$class->parent->literal());

    if ($class->implements) {
      $list= '';
      foreach ($class->implements as $type) {
        $list.= ', '.$type->literal();
      }
      $result->out->write(' implements '.substr($list, 2));
    }

    $result->out->write('{');
    foreach ($class->body as $member) {
      $this->emitOne($result, $member);
    }

    // Virtual properties support: __virtual member + __get() and __set()
    if ($context->virtual) {
      $result->out->write('private $__virtual= [');
      foreach ($context->virtual as $name => $access) {
        $name && $result->out->write("'{$name}' => null,");
      }
      $result->out->write('];');

      $result->out->write('public function __get($name) { switch ($name) {');
      foreach ($context->virtual as $name => $access) {
        $result->out->write($name ? 'case "'.$name.'":' : 'default:');
        $this->emitOne($result, $access[0]);
        $result->out->write(';break;');
      }
      isset($context->virtual[null]) || $result->out->write(
        'default: trigger_error("Undefined property ".__CLASS__."::".$name, E_USER_WARNING);'
      );
      $result->out->write('}}');

      $result->out->write('public function __set($name, $value) { switch ($name) {');
      foreach ($context->virtual as $name => $access) {
        $result->out->write($name ? 'case "'.$name.'":' : 'default:');
        $this->emitOne($result, $access[1]);
        $result->out->write(';break;');
      }
      $result->out->write('}}');
    }

    // Create constructor for property initializations to non-static scalars
    // which have not already been emitted inside constructor
    if ($context->init) {
      $t= $result->temp();
      $result->out->write("public function __construct(... {$t}) {");

      // If existant, invoke parent constructor, passing all parameters as arguments
      if (($parent= $result->codegen->lookup('parent')) && $parent->providesMethod('__construct')) {
        $result->out->write("parent::__construct(... {$t});");
      }

      $this->emitInitializations($result, $context->init);
      $result->out->write('}');
    }

    $result->out->write('static function __init() {');
    $this->emitInitializations($result, $context->statics);
    $this->emitMeta($result, $class->name, $class->annotations, $class->comment);
    $result->out->write('}} '.$class->declaration().'::__init();');

    $result->codegen->leave();
  }

  protected function emitMeta($result, $name, $annotations, $comment) {
    // NOOP
  }

  protected function emitComment($result, $comment) {
    $result->out->write($comment->declaration);
    $result->line+= substr_count($comment->declaration, "\n");
  }

  protected function emitAnnotation($result, $annotation) {
    $result->out->write('\\'.$annotation->name);
    if (empty($annotation->arguments)) return;

    // Check whether arguments are constant
    foreach ($annotation->arguments as $argument) {
      if ($this->isConstant($result, $argument)) continue;

      // Found first non-constant argument, enclose in `eval`
      $escaping= new Escaping($result->out, ["'" => "\\'", '\\' => '\\\\']);
      $result->out->write('(eval: [');
      foreach ($annotation->arguments as $name => $argument) {
        is_string($name) && $result->out->write("'{$name}'=>");

        $result->out->write("'");
        $result->out= $escaping;
        $this->emitOne($result, $argument);
        $result->out= $escaping->original();
        $result->out->write("',");
      }
      $result->out->write('])');
      return;
    }

    $result->out->write('(');
    $this->emitArguments($result, $annotation->arguments);
    $result->out->write(')');
  }

  protected function emitAnnotations($result, $annotations) {
    $result->out->write('#[');
    foreach ($annotations->named as $annotation) {
      $this->emitOne($result, $annotation);
      $result->out->write(',');
    }
    $result->out->write(']');
  }

  protected function emitInterface($result, $interface) {
    $result->codegen->enter(new InType($interface));

    $interface->comment && $this->emitOne($result, $interface->comment);
    $interface->annotations && $this->emitOne($result, $interface->annotations);
    $result->at($interface->declared)->out->write('interface '.$interface->declaration());
    $interface->parents && $result->out->write(' extends '.implode(', ', $interface->parents));

    $result->out->write('{');
    foreach ($interface->body as $member) {
      $this->emitOne($result, $member);
    }
    $result->out->write('}');

    $this->emitMeta($result, $interface->name, $interface->annotations, $interface->comment);
    $result->codegen->leave();
  }

  protected function emitTrait($result, $trait) {
    $result->codegen->enter(new InType($trait));

    $trait->comment && $this->emitOne($result, $trait->comment);
    $trait->annotations && $this->emitOne($result, $trait->annotations);
    $result->at($trait->declared)->out->write('trait '.$trait->declaration());
    $result->out->write('{');
    foreach ($trait->body as $member) {
      $this->emitOne($result, $member);
    }
    $result->out->write('}');

    $this->emitMeta($result, $trait->name, $trait->annotations, $trait->comment);
    $result->codegen->leave();
  }

  protected function emitUse($result, $use) {
    $result->out->write('use '.implode(',', $use->types));

    // Verify Override
    $self= $result->codegen->lookup('self');
    foreach ($use->types as $type) {
      $result->codegen->lookup($type)->checkOverrides($self);
    }

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
    $result->codegen->scope[0]->meta[self::CONSTANT][$const->name]= [
      DETAIL_RETURNS     => $const->type ? $const->type->name() : 'var',
      DETAIL_ANNOTATIONS => $const->annotations,
      DETAIL_COMMENT     => $const->comment,
      DETAIL_TARGET_ANNO => [],
      DETAIL_ARGUMENTS   => []
    ];

    $const->comment && $this->emitOne($result, $const->comment);
    $const->annotations && $this->emitOne($result, $const->annotations);
    $result->at($const->declared)->out->write(implode(' ', $const->modifiers).' const '.$this->constantType($const->type).' '.$const->name.'=');
    $this->emitOne($result, $const->expression);
    $result->out->write(';');
  }

  protected function emitProperty($result, $property) {
    $result->codegen->scope[0]->meta[self::PROPERTY][$property->name]= [
      DETAIL_RETURNS     => $property->type ? $property->type->name() : 'var',
      DETAIL_ANNOTATIONS => $property->annotations,
      DETAIL_COMMENT     => $property->comment,
      DETAIL_TARGET_ANNO => [],
      DETAIL_ARGUMENTS   => []
    ];

    $property->comment && $this->emitOne($result, $property->comment);
    $property->annotations && $this->emitOne($result, $property->annotations);
    $result->at($property->declared)->out->write(implode(' ', $property->modifiers).' '.$this->propertyType($property->type).' $'.$property->name);
    if (isset($property->expression)) {
      if ($this->isConstant($result, $property->expression)) {
        $result->out->write('=');
        $this->emitOne($result, $property->expression);
      } else if (in_array('static', $property->modifiers)) {
        $result->codegen->scope[0]->statics['self::$'.$property->name]= $property->expression;
      } else {
        $result->codegen->scope[0]->init['$this->'.$property->name]= $property->expression;
      }
    }
    $result->out->write(';');
  }

  protected function emitMethod($result, $method) {
    $locals= $result->locals;
    $result->locals= ['this' => true];
    $meta= [
      DETAIL_RETURNS     => $method->signature->returns ? $method->signature->returns->name() : 'var',
      DETAIL_ANNOTATIONS => $method->annotations,
      DETAIL_COMMENT     => $method->comment,
      DETAIL_TARGET_ANNO => [],
      DETAIL_ARGUMENTS   => []
    ];

    $method->comment && $this->emitOne($result, $method->comment);
    if ($method->annotations) {
      $this->emitOne($result, $method->annotations);
      $method->annotations->named(Override::class) && $result->codegen->lookup('self')->checkOverride(
        $method->name,
        $method->line
      );
    }

    $result->at($method->declared)->out->write(
      implode(' ', $method->modifiers).
      ' function '.
      ($method->signature->byref ? '&' : '').
      $method->name
    );

    $promoted= $init= [];
    foreach ($method->signature->parameters as $param) {
      if (isset($param->promote)) $promoted[]= $param;

      // Create a parameter annotation named `Default` for non-constant parameter defaults
      if (isset($param->default) && !$this->isConstant($result, $param->default)) {
        $param->annotate(new Annotation('Default', [$param->default]));
        $init[]= $param;
      }

      $meta[DETAIL_TARGET_ANNO][$param->name]= $param->annotations;
      $meta[DETAIL_ARGUMENTS][]= $param->type ? $param->type->name() : 'var';
    }
    $this->emitSignature($result, $method->signature);

    if (null === $method->body) {
      $result->out->write(';');
    } else {
      $result->out->write(' {');

      // Emit non-constant parameter defaults
      foreach ($init as $param) {
        $result->out->write('$'.$param->name.' ?? $'.$param->name.'=');
        $this->emitOne($result, $param->default);
        $result->out->write(';');
      }

      // Emit promoted parameters
      foreach ($promoted as $param) {
        $result->out->write('$this->'.$param->name.($param->reference ? '=&$' : '=$').$param->name.';');
      }

      // Emit initializations if inside constructor
      if ('__construct' === $method->name) {
        $this->emitInitializations($result, $result->codegen->scope[0]->init);
        $result->codegen->scope[0]->init= [];
      }

      $this->emitAll($result, $method->body);
      $result->out->write('}');
    }

    foreach ($promoted as $param) {
      $this->emitProperty($result, new Property(explode(' ', $param->promote), $param->name, $param->type));
    }

    $result->locals= $locals;
    $result->codegen->scope[0]->meta[self::METHOD][$method->name]= $meta;
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
    $result->out->write($unary->operator.' ');
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
    if ($target instanceof Variable && $target->const) {
      $result->out->write('$'.$target->pointer);
      $result->locals[$target->pointer]= true;
    } else if ($target instanceof ArrayLiteral) {
      $result->out->write('list(');
      foreach ($target->values as $pair) {
        if ($pair[0]) {
          $this->emitOne($result, $pair[0]);
          $result->out->write('=>');
        }
        if ($pair[1]) {
          $this->emitAssign($result, $pair[1]);
        }
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
    if (null === $match->expression) {
      $result->out->write('match (true) {');
    } else {
      $result->out->write('match (');
      $this->emitOne($result, $match->expression);
      $result->out->write(') {');
    }

    foreach ($match->cases as $case) {
      $b= 0;
      foreach ($case->expressions as $expression) {
        $b && $result->out->write(',');
        $this->emitOne($result, $expression);
        $b++;
      }
      $result->out->write('=>');
      $this->emitAsExpression($result, $case->body);
      $result->out->write(',');
    }

    if ($match->default) {
      $result->out->write('default=>');
      $this->emitAsExpression($result, $match->default);
    }

    $result->out->write('}');
  }

  protected function emitCatch($result, $catch) {
    $capture= $catch->variable ? ' $'.$catch->variable : '';
    if (empty($catch->types)) {
      $result->out->write('catch(\\Throwable'.$capture.') {');
    } else {
      $result->out->write('catch('.implode('|', $catch->types).$capture.') {');
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
    $result->out->write('throw ');
    $this->emitOne($result, $throw->expression);
  }

  protected function emitForeach($result, $foreach) {
    $result->out->write('foreach (');
    $this->emitOne($result, $foreach->expression);
    $result->out->write(' as ');
    if ($foreach->key) {
      $this->emitOne($result, $foreach->key);
      $result->out->write(' => ');
    }

    // Support empty elements: `foreach (<expr> as [$a, , $b])`
    if ($foreach->value instanceof ArrayLiteral) {
      $result->out->write('[');
      foreach ($foreach->value->values as $pair) {
        if ($pair[0]) {
          $this->emitOne($result, $pair[0]);
          $result->out->write('=>');
        }
        $pair[1] && $this->emitOne($result, $pair[1]);
        $result->out->write(',');
      }
      $result->out->write(']');
    } else {
      $this->emitOne($result, $foreach->value);
    }
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
    $i= 0;
    foreach ($arguments as $name => $argument) {
      if ($i++) $result->out->write(',');
      if (is_string($name)) $result->out->write($name.':');
      $this->emitOne($result, $argument);
    }
  }

  protected function emitNew($result, $new) {
    if ($new->type instanceof IsExpression) {
      $result->out->write('new (');
      $this->emitOne($result, $new->type->expression);
      $result->out->write(')(');
    } else {
      $result->out->write('new '.$new->type->literal().'(');
    }

    $this->emitArguments($result, $new->arguments);
    $result->out->write(')');
  }

  protected function emitNewClass($result, $new) {
    $enclosing= $result->codegen->scope[0] ?? null;

    $result->out->write('(new class(');
    $this->emitArguments($result, $new->arguments);
    $result->out->write(')');

    // Allow "extends self" to reference enclosing class (except if this
    // class is an anonymous class!)
    if ($enclosing && $new->definition->parent && 'self' === $new->definition->parent->name()) {
      $result->out->write(' extends '.$enclosing->type->name->literal());
    } else if ($new->definition->parent) {
      $result->out->write(' extends '.$new->definition->parent->literal());
    }

    if ($new->definition->implements) {
      $list= '';
      foreach ($new->definition->implements as $type) {
        $list.= ', '.$type->literal();
      }
      $result->out->write(' implements '.substr($list, 2));
    }

    $result->codegen->enter(new InType($new->definition));
    $result->out->write('{');
    foreach ($new->definition->body as $member) {
      $this->emitOne($result, $member);
    }
    $result->out->write('function __new() {');
    $this->emitMeta($result, null, [], null);
    $result->out->write('return $this; }})->__new()');
    $result->codegen->leave();
  }

  protected function emitCallable($result, $callable) {

    // Disambiguate the following:
    //
    // - `T::{$func}`, a dynamic class constant
    // - `T::{$func}(...)`, a dynamic first-class callable
    $callable->expression->line= -1;

    $this->emitOne($result, $callable->expression);
    $result->out->write('(...)');
  }

  protected function emitCallableNew($result, $callable) {
    $t= $result->temp();
    $result->out->write("function(...{$t}) { return ");

    $callable->type->arguments= [new UnpackExpression(new Variable(substr($t, 1)), $callable->line)];
    $this->emitOne($result, $callable->type);
    $callable->type->arguments= null;

    $result->out->write("; }");
  }

  protected function emitInvoke($result, $invoke) {
    $this->emitOne($result, $invoke->expression);
    $result->out->write('(');
    $this->emitArguments($result, $invoke->arguments);
    $result->out->write(')');
  }

  protected function emitScope($result, $scope) {

    // $x::<expr> vs. e.g. invoke()::<expr> vs. T::<expr>
    if ($scope->type instanceof Variable) {
      $this->emitOne($result, $scope->type);
      $result->out->write('::');
    } else if ($scope->type instanceof Node) {
      $t= $result->temp();
      $result->out->write('(null==='.$t.'=');
      $this->emitOne($result, $scope->type);
      $result->out->write(")?null:{$t}::");
    } else {
      $result->out->write("{$scope->type}::");
    }

    // Rewrite T::member to T::$member for XP enums
    if (
      $scope->member instanceof Literal &&
      is_string($scope->type) &&
      'class' !== $scope->member->expression &&
      $result->codegen->lookup($scope->type)->rewriteEnumCase($scope->member->expression)
    ) {
      $result->out->write('$'.$scope->member->expression);
    } else {
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

    $this->emitOne($result, $instance->member);
  }

  protected function emitNullsafeInstance($result, $instance) {
    $this->emitOne($result, $instance->expression);
    $result->out->write('?->');
    $this->emitOne($result, $instance->member);
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

  /**
   * Emit single nodes
   *
   * @param  lang.ast.Result $result
   * @param  lang.ast.Node $node
   * @return void
   */
  public function emitOne($result, $node) {
    parent::emitOne($result->at($node->line), $node);
  }
}