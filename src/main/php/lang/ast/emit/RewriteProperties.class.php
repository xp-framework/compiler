<?php namespace lang\ast\emit;

trait RewriteProperties {
  use PropertyHooks, ReadonlyProperties {
    PropertyHooks::emitProperty as emitPropertyHooks;
    ReadonlyProperties::emitProperty as emitReadonlyProperties;
  }

  protected function emitProperty($result, $property) {
    if ($property->hooks) {
      return $this->emitPropertyHooks($result, $property);
    } else if (in_array('readonly', $property->modifiers)) {
      return $this->emitReadonlyProperties($result, $property);
    }
    parent::emitProperty($result, $property);
  }
}