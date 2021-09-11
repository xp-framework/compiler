<?php namespace lang\ast\emit;

use lang\ast\Code;

/**
 * Creates __get() and __set() overloads for readonly properties
 *
 * @see  https://github.com/xp-framework/compiler/issues/115
 * @see  https://wiki.php.net/rfc/readonly_properties_v2
 */
trait ReadonlyProperties {

  protected function emitProperty($result, $property) {
    $p= array_search('readonly', $property->modifiers);
    if (false === $p) return parent::emitProperty($result, $property);

    $result->meta[0][self::PROPERTY][$property->name]= [
      DETAIL_RETURNS     => $property->type ? $property->type->name() : 'var',
      DETAIL_ANNOTATIONS => $property->annotations,
      DETAIL_COMMENT     => $property->comment,
      DETAIL_TARGET_ANNO => [],
      DETAIL_ARGUMENTS   => [MODIFIER_READONLY]
    ];

    // Create virtual property implementing the readonly semantics
    $result->locals[2][$property->name]= [
      new Code('return $this->__virtual["'.$property->name.'"][0] ?? null;'),
      new Code('
        if (isset($this->__virtual["'.$property->name.'"])) {
          throw new \\Error("Cannot modify readonly property ".__CLASS__."::{$name}");
        }
        $caller= debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1];
        $scope= $caller["class"] ?? null;
        if (__CLASS__ !== $scope && \\lang\\VirtualProperty::class !== $scope) {
          throw new \\Error("Cannot initialize readonly property ".__CLASS__."::{$name} from ".($scope
            ? "scope {$scope}"
            : "global scope"
          ));
        }
        $this->__virtual["'.$property->name.'"]= [$value];
      '),
    ];

    if (isset($property->expression)) {
      if ($this->isConstant($result, $property->expression)) {
        $result->out->write('=');
        $this->emitOne($result, $property->expression);
      } else if (in_array('static', $property->modifiers)) {
        $result->locals[0]['self::$'.$property->name]= $property->expression;
      } else {
        $result->locals[1]['$this->'.$property->name]= $property->expression;
      }
    }
  }
}