<?php namespace lang\ast\emit;

use ReflectionProperty;

trait RewriteProperties {
  use PropertyHooks, ReadonlyProperties, AsymmetricVisibility {
    PropertyHooks::emitProperty as emitPropertyHooks;
    ReadonlyProperties::emitProperty as emitReadonlyProperties;
    AsymmetricVisibility::emitProperty as emitAsymmetricVisibility;
  }

  protected function emitProperty($result, $property) {
    static $asymmetric= null;
    if ($property->hooks) {
      return $this->emitPropertyHooks($result, $property);
    } else if (
      !($asymmetric ?? $asymmetric= method_exists(ReflectionProperty::class, 'isPrivateSet')) &&
      array_intersect($property->modifiers, ['private(set)', 'protected(set)', 'public(set)'])
    ) {
      return $this->emitAsymmetricVisibility($result, $property);
    } else if (PHP_VERSION_ID <= 80100 && in_array('readonly', $property->modifiers)) {
      return $this->emitReadonlyProperties($result, $property);
    }
    parent::emitProperty($result, $property);
  }
}