<?php namespace lang\ast\emit;

/**
 * PHP 7.0 syntax
 *
 * @see  https://wiki.php.net/rfc/generator-delegation - Not yet implemented
 * @see  https://wiki.php.net/rfc/generator-return-expressions - Not yet implemented
 * @see  https://wiki.php.net/rfc/anonymous_classes
 * @see  https://wiki.php.net/rfc/return_types
 * @see  https://wiki.php.net/rfc/isset_ternary
 * @see  https://wiki.php.net/rfc/uniform_variable_syntax
 * @see  https://wiki.php.net/rfc/group_use_declarations
 * @see  https://wiki.php.net/rfc/scalar_type_hints_v5
 */
class PHP70 extends \lang\ast\Emitter {

  protected function type($name) {
    static $unsupported= ['void' => 71, 'iterable' => 71, 'object' => 72];

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

  protected function emitConst($node) {
    $this->out->write('const '.$node->value[0].'=');
    $this->emit($node->value[2]);
    $this->out->write(';');
  }
}