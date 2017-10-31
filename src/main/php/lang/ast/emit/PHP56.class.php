<?php namespace lang\ast\emit;

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
    $last= array_pop($catch[0]);
    $label= sprintf('c%u', crc32($last));
    foreach ($catch[0] as $type) {
      $this->out->write('catch('.$type.' $'.$catch[1].') { goto '.$label.'; }');
    }

    $this->out->write('catch('.$last.' $'.$catch[1].') { '.$label.':');
    $this->emit($catch[2]);
    $this->out->write('}');
  }

  protected function emitConst($node) {
    $this->out->write('const '.$node->value[0].'=');
    $this->emit($node->value[2]);
    $this->out->write(';');
  }

  protected function emitAssignment($node) {
    if ('array' === $node->value[0]->arity) {
      $this->out->write('list(');
      foreach ($node->value[0]->value as $expr) {
        $this->emit($expr[1]);
        $this->out->write(',');
      }
      $this->out->write(')');
      $this->out->write($node->symbol->id);
      $this->emit($node->value[1]);
    } else {
      parent::emitAssignment($node);
    }
  }

  protected function emitBinary($node) {
    if ('??' === $node->symbol->id) {
      $this->out->write('isset(');
      $this->emit($node->value[0]);
      $this->out->write(') ?');
      $this->emit($node->value[0]);
      $this->out->write(' : ');
      $this->emit($node->value[1]);
    } else if ('<=>' === $node->symbol->id) {
      $l= $this->temp();
      $r= $this->temp();
      $this->out->write('('.$l.'= ');
      $this->emit($node->value[0]);
      $this->out->write(') < ('.$r.'=');
      $this->emit($node->value[1]);
      $this->out->write(') ? -1 : ('.$l.' == '.$r.' ? 0 : 1)');
    } else {
      parent::emitBinary($node);
    }
  }

  /** @see https://wiki.php.net/rfc/context_sensitive_lexer */
  protected function emitInvoke($node) {
    $expr= $node->value[0];
    if ('braced' === $expr->arity) {
      $t= $this->temp();
      $this->out->write('(('.$t.'=');
      $this->emit($expr->value);
      $this->out->write(') ? '.$t);
      $this->out->write('(');
      $this->arguments($node->value[1]);
      $this->out->write(') : __error(E_RECOVERABLE_ERROR, "Function name must be a string", __FILE__, __LINE__))');
    } else if ('scope' === $expr->arity && 'name' === $expr->value[1]->arity && isset(self::$keywords[strtolower($expr->value[1]->value)])) {
      $this->out->write($expr->value[0].'::{\''.$expr->value[1]->value.'\'}');
      $this->out->write('(');
      $this->arguments($node->value[1]);
      $this->out->write(')');
    } else {
      parent::emitInvoke($node);
    }
  }

  protected function emitNew($node) {
    if (null === $node->value[0]) {
      $this->out->write('\\lang\\ClassLoader::defineType("class©anonymous'.md5($node->hashCode()).'", ["kind" => "class"');
      $definition= $node->value[2];
      $this->out->write(', "extends" => '.($definition[2] ? '[\''.$definition[2].'\']' : 'null'));
      $this->out->write(', "implements" => '.($definition[3] ? '[\''.implode('\', \'', $definition[3]).'\']' : 'null'));
      $this->out->write(', "use" => []');
      $this->out->write('], \'{');
      $this->out->write(str_replace('\'', '\\\'', $this->buffer(function() use($definition) {
        foreach ($definition[4] as $member) {
          $this->emit($member);
          $this->out->write("\n");
        }
      })));
      $this->out->write('}\')->newInstance(');
      $this->arguments($definition[1]);
      $this->out->write(')');
    } else {
      parent::emitNew($node);
    }
  }

  protected function emitFrom($node) {
    $this->out->write('foreach (');
    $this->emit($node->value);
    $this->out->write(' as $key => $val) yield $key => $val;');
  }

  /** @see https://wiki.php.net/rfc/context_sensitive_lexer */
  protected function emitMethod($node) {
    if (isset(self::$keywords[strtolower($node->value[0])])) {
      $this->call[in_array('static', $node->value[1])][]= $node->value[0];
      $node->value[0]= '__'.$node->value[0];
    } else if ('__call' === $node->value[0] || '__callStatic' === $node->value[0]) {
      $node->value[0].= '0';
    }
    parent::emitMethod($node);
  }

  protected function emitClass($node) {
    $this->call= [false => [], true => []];
    array_unshift($this->meta, []);
    $this->out->write(implode(' ', $node->value[1]).' class '.$this->declaration($node->value[0]));
    $node->value[2] && $this->out->write(' extends '.$node->value[2]);
    $node->value[3] && $this->out->write(' implements '.implode(', ', $node->value[3]));
    $this->out->write('{');
    foreach ($node->value[4] as $member) {
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

    $this->emitMeta($node);
    $this->out->write('} '.$node->value[0].'::__init();');
  }
}