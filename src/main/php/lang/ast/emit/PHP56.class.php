<?php namespace lang\ast\emit;

use lang\ast\nodes\Kind;

/**
 * PHP 5.6 syntax
 *
 * @see  https://wiki.php.net/rfc/pow-operator
 * @see  https://wiki.php.net/rfc/variadics
 * @see  https://wiki.php.net/rfc/argument_unpacking
 * @see  https://wiki.php.net/rfc/use_function - Not yet implemented
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

  protected function catches($catch) {
    $last= array_pop($catch->types);
    $label= sprintf('c%u', crc32($last));
    foreach ($catch->types as $type) {
      $this->out->write('catch('.$type.' $'.$catch->variable.') { goto '.$label.'; }');
    }

    $this->out->write('catch('.$last.' $'.$catch->variable.') { '.$label.':');
    $this->emit($catch->body);
    $this->out->write('}');
  }

  protected function emitConst($kind) {
    $this->out->write('const '.$kind->name.'=');
    $this->emit($kind->expression);
    $this->out->write(';');
  }

  protected function emitAssignment($kind) {
    if ('array' === $kind->variable->arity) {
      $this->out->write('list(');
      foreach ($kind->variable->value as $pair) {
        $this->emit($pair[1]);
        $this->out->write(',');
      }
      $this->out->write(')');
      $this->out->write($kind->operator);
      $this->emit($kind->expression);
    } else {
      parent::emitAssignment($kind);
    }
  }

  protected function emitBinary($kind) {
    if ('??' === $kind->operator) {
      $this->out->write('isset(');
      $this->emit($kind->left);
      $this->out->write(') ?');
      $this->emit($kind->left);
      $this->out->write(' : ');
      $this->emit($kind->right);
    } else if ('<=>' === $kind->operator) {
      $l= $this->temp();
      $r= $this->temp();
      $this->out->write('('.$l.'= ');
      $this->emit($kind->left);
      $this->out->write(') < ('.$r.'=');
      $this->emit($kind->right);
      $this->out->write(') ? -1 : ('.$l.' == '.$r.' ? 0 : 1)');
    } else {
      parent::emitBinary($kind);
    }
  }

  /** @see https://wiki.php.net/rfc/context_sensitive_lexer */
  protected function emitInvoke($kind) {
    $expr= $kind->expression;
    if ('braced' === $expr->arity) {
      $t= $this->temp();
      $this->out->write('(('.$t.'=');
      $this->emit($expr->value);
      $this->out->write(') ? '.$t);
      $this->out->write('(');
      $this->arguments($kind->arguments);
      $this->out->write(') : __error(E_RECOVERABLE_ERROR, "Function name must be a string", __FILE__, __LINE__))');
    } else if (
      'scope' === $expr->arity &&
      'name' === $expr->value->member->arity &&
      isset(self::$keywords[strtolower($expr->value->member->value)])
    ) {
      $this->out->write($expr->value->type.'::{\''.$expr->value->member->value.'\'}');
      $this->out->write('(');
      $this->arguments($kind->arguments);
      $this->out->write(')');
    } else {
      parent::emitInvoke($kind);
    }
  }

  protected function emitNew($kind) {
    if ($kind->type instanceof Kind) {
      $this->out->write('\\lang\\ClassLoader::defineType("class©anonymous'.md5(uniqid()).'", ["kind" => "class"');
      $definition= $kind->type;
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
      $this->arguments($kind->arguments);
      $this->out->write(')');
    } else {
      parent::emitNew($kind);
    }
  }

  protected function emitFrom($kind) {
    $this->out->write('foreach (');
    $this->emit($kind);
    $this->out->write(' as $key => $val) yield $key => $val;');
  }

  /** @see https://wiki.php.net/rfc/context_sensitive_lexer */
  protected function emitMethod($kind) {
    if (isset(self::$keywords[strtolower($kind->name)])) {
      $this->call[in_array('static', $kind->modifiers)][]= $kind->name;
      $kind->name= '__'.$kind->name;
    } else if ('__call' === $kind->name || '__callStatic' === $kind->name) {
      $kind->name.= '0';
    }
    parent::emitMethod($kind);
  }

  protected function emitClass($kind) {
    $this->call= [false => [], true => []];
    array_unshift($this->meta, []);
    $this->out->write(implode(' ', $kind->modifiers).' class '.$this->declaration($kind->name));
    $kind->parent && $this->out->write(' extends '.$kind->parent);
    $kind->implements && $this->out->write(' implements '.implode(', ', $kind->implements));
    $this->out->write('{');
    foreach ($kind->body as $member) {
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
    $this->emitMeta($kind->name, $kind->annotations, $kind->comment);
    $this->out->write('}} '.$kind->name.'::__init();');
  }
}