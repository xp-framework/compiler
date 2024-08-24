<?php namespace lang\ast\emit;

trait RewriteProperties {
  use PropertyHooks, ReadonlyProperties, AsymmetricVisibility {
    PropertyHooks::emitProperty as emitPropertyHooks;
    ReadonlyProperties::emitProperty as emitReadonlyProperties;
    AsymmetricVisibility::emitProperty as emitAsymmetricVisibility;
  }

  protected function emitProperty($result, $property) {
    if ($property->hooks) {
      return $this->emitPropertyHooks($result, $property);
    } else if (in_array('private(set)', $property->modifiers)) {
      return $this->emitAsymmetricVisibility($result, $property);
    } else if (PHP_VERSION_ID <= 80100 && in_array('readonly', $property->modifiers)) {
      return $this->emitReadonlyProperties($result, $property);
    }
    parent::emitProperty($result, $property);
  }
}