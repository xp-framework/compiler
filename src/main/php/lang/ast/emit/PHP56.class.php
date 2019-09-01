<?php namespace lang\ast\emit;

use lang\ast\Emitter;
use lang\ast\nodes\Value;

/**
 * PHP 5.6 syntax
 *
 * @see  https://wiki.php.net/rfc#php_56
 */
class PHP56 extends Emitter {
  use OmitPropertyTypes, OmitReturnTypes, OmitConstModifiers;
  use RewriteLambdaExpressions;

  protected $unsupported= [
    'object'   => 72,
    'void'     => 71,
    'iterable' => 71,
    'string'   => 70,
    'int'      => 70,
    'bool'     => 70,
    'float'    => 70,
    'mixed'    => null,
  ];
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


  protected function emitLiteral($result, $literal) {
    if ('"' === $literal{0}) {
      $result->out->write(preg_replace_callback(
        '/\\\\u\{([0-9a-f]+)\}/i',
        function($matches) { return html_entity_decode('&#'.hexdec($matches[1]).';', ENT_HTML5, \xp::ENCODING); },
        $literal
      ));
    } else {
      $result->out->write($literal);
    }
  }

  protected function emitCatch($result, $catch) {
    if (empty($catch->types)) {
      $result->out->write('catch(\\Exception $'.$catch->variable.') {');
    } else {
      $last= array_pop($catch->types);
      $label= sprintf('c%u', crc32($last));
      foreach ($catch->types as $type) {
        $result->out->write('catch('.$type.' $'.$catch->variable.') { goto '.$label.'; }');
      }
      $result->out->write('catch('.$last.' $'.$catch->variable.') { '.$label.':');
    }

    $this->emit($result, $catch->body);
    $result->out->write('}');
  }

  protected function emitBinary($result, $binary) {
    if ('??' === $binary->operator) {
      $result->out->write('isset(');
      $this->emit($result, $binary->left);
      $result->out->write(') ?');
      $this->emit($result, $binary->left);
      $result->out->write(' : ');
      $this->emit($result, $binary->right);
    } else if ('<=>' === $binary->operator) {
      $l= $result->temp();
      $r= $result->temp();
      $result->out->write('('.$l.'= ');
      $this->emit($result, $binary->left);
      $result->out->write(') < ('.$r.'=');
      $this->emit($result, $binary->right);
      $result->out->write(') ? -1 : ('.$l.' == '.$r.' ? 0 : 1)');
    } else {
      parent::emitBinary($binary);
    }
  }

  protected function emitAssignment($result, $assignment) {
    if ('??=' === $assignment->operator) {
      $result->out->write('isset(');
      $this->emitAssign($assignment->variable);
      $result->out->write(') ||');
      $this->emit($result, $assignment->variable);
      $result->out->write('=');
      $this->emit($result, $assignment->expression);
    } else {
      $this->emitAssign($assignment->variable);
      $result->out->write($assignment->operator);
      $this->emit($result, $assignment->expression);
    }
  }

  /** @see https://wiki.php.net/rfc/context_sensitive_lexer */
  protected function emitInvoke($result, $invoke) {
    $expr= $invoke->expression;
    if ('braced' === $expr->kind) {
      $t= $result->temp();
      $result->out->write('(('.$t.'=');
      $this->emit($result, $expr->value);
      $result->out->write(') ? '.$t);
      $result->out->write('(');
      $this->emitArguments($invoke->arguments);
      $result->out->write(') : __error(E_RECOVERABLE_ERROR, "Function name must be a string", __FILE__, __LINE__))');
    } else if (
      'scope' === $expr->kind &&
      'name' === $expr->value->member->kind &&
      isset(self::$keywords[strtolower($expr->value->member->value)])
    ) {
      $result->out->write($expr->value->type.'::{\''.$expr->value->member->value.'\'}');
      $result->out->write('(');
      $this->emitArguments($invoke->arguments);
      $result->out->write(')');
    } else {
      parent::emitInvoke($invoke);
    }
  }

  /** @see https://wiki.php.net/rfc/context_sensitive_lexer */
  protected function emitThrowExpression($result, $throw) {
    $capture= [];
    foreach ($this->search($throw, 'variable') as $var) {
      if (isset($result->locals[$var->value])) {
        $capture[$var->value]= true;
      }
    }
    unset($capture['this']);

    $t= $result->temp();
    $result->out->write('(('.$t.'=function()');
    $capture && $result->out->write(' use($'.implode(', $', array_keys($capture)).')');
    $result->out->write('{ throw ');
    $this->emit($result, $throw);
    $result->out->write('; }) ? '.$t.'() : null)');
  }

  protected function emitNewClass($result, $new) {
    $result->out->write('\\lang\\ClassLoader::defineType("class©anonymous'.md5(uniqid()).'", ["kind" => "class"');
    $definition= $new->definition;
    $result->out->write(', "extends" => '.($definition->parent ? '[\''.$definition->parent.'\']' : 'null'));
    $result->out->write(', "implements" => '.($definition->implements ? '[\''.implode('\', \'', $definition->implements).'\']' : 'null'));
    $result->out->write(', "use" => []');
    $result->out->write('], \'{');
    $result->out->write(str_replace('\'', '\\\'', $this->buffer(function() use($definition) {
      foreach ($definition->body as $member) {
        $this->emit($result, $member);
        $result->out->write("\n");
      }
    })));
    $result->out->write('}\')->newInstance(');
    $this->emitArguments($result, $new->arguments);
    $result->out->write(')');
  }

  protected function emitFrom($result, $from) {
    $result->out->write('foreach (');
    $this->emit($result, $from);
    $result->out->write(' as $key => $val) yield $key => $val;');
  }

  /** @see https://wiki.php.net/rfc/context_sensitive_lexer */
  protected function emitMethod($result, $method) {
    if (isset(self::$keywords[strtolower($method->name)])) {
      $result->call[in_array('static', $method->modifiers)][]= $method->name;
      $method->name= '__'.$method->name;
    } else if ('__call' === $method->name || '__callStatic' === $method->name) {
      $method->name.= '0';
    }
    parent::emitMethod($result, $method);
  }

  protected function emitClass($result, $class) {
    $result->call= [false => [], true => []];
    array_unshift($this->meta, []);
    $result->out->write(implode(' ', $class->modifiers).' class '.$this->declaration($class->name));
    $class->parent && $result->out->write(' extends '.$class->parent);
    $class->implements && $result->out->write(' implements '.implode(', ', $class->implements));
    $result->out->write('{');
    foreach ($class->body as $member) {
      $this->emit($result, $member);
    }

    if ($result->call[false]) {
      $result->out->write('function __call($name, $args) {');
      foreach ($this->call[false] as $name) {
        $result->out->write('if (\''.$name.'\' === $name) return $this->__'.$name.'(...$args); else ');
      }
      $result->out->write('return $this->__call0($name, $args); }');
    }
    if ($result->call[true]) {
      $result->out->write('static function __callStatic($name, $args) {');
      foreach ($this->call[true] as $name) {
        $result->out->write('if (\''.$name.'\' === $name) return self::__'.$name.'(...$args); else ');
      }
      $result->out->write('return self::__callStatic0($name, ...$args); }');
    }

    $result->out->write('static function __init() {');
    $this->emitMeta($result, $class->name, $class->annotations, $class->comment);
    $result->out->write('}} '.$class->name.'::__init();');
  }
}