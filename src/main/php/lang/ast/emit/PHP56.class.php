<?php namespace lang\ast\emit;

use lang\ast\nodes\Value;

/**
 * PHP 5.6 syntax
 *
 * @see  https://wiki.php.net/rfc/pow-operator
 * @see  https://wiki.php.net/rfc/variadics
 * @see  https://wiki.php.net/rfc/argument_unpacking
 * @see  https://wiki.php.net/rfc/use_function
 */
class PHP56 extends \lang\ast\Emitter {
  protected $unsupported= [
    'object'   => 72,
    'void'     => 71,
    'iterable' => 71,
    'string'   => 70,
    'int'      => 70,
    'bool'     => 70,
    'float'    => 70
  ];
  private $call= [];
  private static $keywords= [
    'callable'     => true,
    'class'        => true,
    'trait'        => true,
    'extends'      => true,
    'implements'   => true,
    'static'       => true,
    'abstract'     => true,
    'final'        => true,
    'public'       => true,
    'protected'    => true,
    'private'      => true,
    'const'        => true,
    'enddeclare'   => true,
    'endfor'       => true,
    'endforeach'   => true,
    'endif'        => true,
    'endwhile'     => true,
    'and'          => true,
    'global'       => true,
    'goto'         => true,
    'instanceof'   => true,
    'insteadof'    => true,
    'interface'    => true,
    'namespace'    => true,
    'new'          => true,
    'or'           => true,
    'xor'          => true,
    'try'          => true,
    'use'          => true,
    'var'          => true,
    'exit'         => true,
    'list'         => true,
    'clone'        => true,
    'include'      => true,
    'include_once' => true,
    'throw'        => true,
    'array'        => true,
    'print'        => true,
    'echo'         => true,
    'require'      => true,
    'require_once' => true,
    'return'       => true,
    'else'         => true,
    'elseif'       => true,
    'default'      => true,
    'break'        => true,
    'continue'     => true,
    'switch'       => true,
    'yield'        => true,
    'function'     => true,
    'if'           => true,
    'endswitch'    => true,
    'finally'      => true,
    'for'          => true,
    'foreach'      => true,
    'declare'      => true,
    'case'         => true,
    'do'           => true,
    'while'        => true,
    'as'           => true,
    'catch'        => true,
    'die'          => true,
    'self'         => true,
    'parent'       => true
  ];

  protected function returnType($name) {
    return null;
  }

  protected function emitCatch($catch) {
    $last= array_pop($catch->types);
    $label= sprintf('c%u', crc32($last));
    foreach ($catch->types as $type) {
      $this->out->write('catch('.$type.' $'.$catch->variable.') { goto '.$label.'; }');
    }

    $this->out->write('catch('.$last.' $'.$catch->variable.') { '.$label.':');
    $this->emit($catch->body);
    $this->out->write('}');
  }

  protected function emitConst($const) {
    $this->out->write('const '.$const->name.'=');
    $this->emit($const->expression);
    $this->out->write(';');
  }

  protected function emitAssignment($assignment) {
    if ('array' === $assignment->variable->kind) {
      $this->out->write('list(');
      foreach ($assignment->variable->value as $pair) {
        $this->emit($pair[1]);
        $this->out->write(',');
      }
      $this->out->write(')');
      $this->out->write($assignment->operator);
      $this->emit($assignment->expression);
    } else {
      parent::emitAssignment($assignment);
    }
  }

  protected function emitBinary($binary) {
    if ('??' === $binary->operator) {
      $this->out->write('isset(');
      $this->emit($binary->left);
      $this->out->write(') ?');
      $this->emit($binary->left);
      $this->out->write(' : ');
      $this->emit($binary->right);
    } else if ('<=>' === $binary->operator) {
      $l= $this->temp();
      $r= $this->temp();
      $this->out->write('('.$l.'= ');
      $this->emit($binary->left);
      $this->out->write(') < ('.$r.'=');
      $this->emit($binary->right);
      $this->out->write(') ? -1 : ('.$l.' == '.$r.' ? 0 : 1)');
    } else {
      parent::emitBinary($binary);
    }
  }

  /** @see https://wiki.php.net/rfc/context_sensitive_lexer */
  protected function emitInvoke($invoke) {
    $expr= $invoke->expression;
    if ('braced' === $expr->kind) {
      $t= $this->temp();
      $this->out->write('(('.$t.'=');
      $this->emit($expr->value);
      $this->out->write(') ? '.$t);
      $this->out->write('(');
      $this->emitArguments($invoke->arguments);
      $this->out->write(') : __error(E_RECOVERABLE_ERROR, "Function name must be a string", __FILE__, __LINE__))');
    } else if (
      'scope' === $expr->kind &&
      'name' === $expr->value->member->kind &&
      isset(self::$keywords[strtolower($expr->value->member->value)])
    ) {
      $this->out->write($expr->value->type.'::{\''.$expr->value->member->value.'\'}');
      $this->out->write('(');
      $this->emitArguments($invoke->arguments);
      $this->out->write(')');
    } else {
      parent::emitInvoke($invoke);
    }
  }

  protected function emitNewClass($new) {
    $this->out->write('\\lang\\ClassLoader::defineType("class©anonymous'.md5(uniqid()).'", ["kind" => "class"');
    $definition= $new->definition;
    $this->out->write(', "extends" => '.($definition->parent ? '[\''.$definition->parent.'\']' : 'null'));
    $this->out->write(', "implements" => '.($definition->implements ? '[\''.implode('\', \'', $definition->implements).'\']' : 'null'));
    $this->out->write(', "use" => []');
    $this->out->write('], \'{');
    $this->out->write(str_replace('\'', '\\\'', $this->buffer(function() use($definition) {
      foreach ($definition->body as $member) {
        $this->emit($member);
        $this->out->write("\n");
      }
    })));
    $this->out->write('}\')->newInstance(');
    $this->emitArguments($new->arguments);
    $this->out->write(')');
  }

  protected function emitFrom($from) {
    $this->out->write('foreach (');
    $this->emit($from);
    $this->out->write(' as $key => $val) yield $key => $val;');
  }

  /** @see https://wiki.php.net/rfc/context_sensitive_lexer */
  protected function emitMethod($method) {
    if (isset(self::$keywords[strtolower($method->name)])) {
      $this->call[in_array('static', $method->modifiers)][]= $method->name;
      $method->name= '__'.$method->name;
    } else if ('__call' === $method->name || '__callStatic' === $method->name) {
      $method->name.= '0';
    }
    parent::emitMethod($method);
  }

  protected function emitClass($class) {
    $this->call= [false => [], true => []];
    array_unshift($this->meta, []);
    $this->out->write(implode(' ', $class->modifiers).' class '.$this->declaration($class->name));
    $class->parent && $this->out->write(' extends '.$class->parent);
    $class->implements && $this->out->write(' implements '.implode(', ', $class->implements));
    $this->out->write('{');
    foreach ($class->body as $member) {
      $this->emit($member);
    }

    if ($this->call[false]) {
      $this->out->write('function __call($name, $args) {');
      foreach ($this->call[false] as $name) {
        $this->out->write('if (\''.$name.'\' === $name) return $this->__'.$name.'(...$args); else ');
      }
      $this->out->write('return $this->__call0($name, $args); }');
    }
    if ($this->call[true]) {
      $this->out->write('static function __callStatic($name, $args) {');
      foreach ($this->call[true] as $name) {
        $this->out->write('if (\''.$name.'\' === $name) return self::__'.$name.'(...$args); else ');
      }
      $this->out->write('return self::__callStatic0($name, ...$args); }');
    }

    $this->out->write('static function __init() {');
    $this->emitMeta($class->name, $class->annotations, $class->comment);
    $this->out->write('}} '.$class->name.'::__init();');
  }
}