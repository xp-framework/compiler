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

  protected function type($name) {
    static $unsupported= [
      'object'   => 72,
      'void'     => 71,
      'iterable' => 71,
      'string'   => 70,
      'int'      => 70,
      'bool'     => 70,
      'float'    => 70
    ];

    if ('?' === $name{0} || isset($unsupported[$name])) return null;
    return $name;
  }

  protected function catches($catch) {
    $last= array_pop($catch[0]);
    $label= 'c'.crc32($last);
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
    if ('[' === $node->value[0]->symbol->id) {
      $this->out->write('list(');
      $this->arguments($node->value[0]->value);
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
}