<?php namespace lang\ast\emit;

use ReflectionProperty;

trait RewriteProperties {
  use PropertyHooks, FinalProperties, ReadonlyProperties, AsymmetricVisibility {
    PropertyHooks::emitProperty as emitPropertyHooks;
    FinalProperties::emitProperty as emitFinalProperties;
    ReadonlyProperties::emitProperty as emitReadonlyProperties;
    AsymmetricVisibility::emitProperty as emitAsymmetricVisibility;
  }

  protected function emitProperty($result, $property) {
    if ($property->hooks) {
      return $this->emitPropertyHooks($result, $property);
    } else if (
      $this->targetVersion < 80400 &&
      array_intersect($property->modifiers, ['private(set)', 'protected(set)', 'public(set)'])
    ) {
      return $this->emitAsymmetricVisibility($result, $property);
    } else if (
      $this->targetVersion < 80400 &&
      in_array('final', $property->modifiers)
    ) {
      return $this->emitFinalProperties($result, $property);
    } else if (
      $this->targetVersion < 80100 &&
      in_array('readonly', $property->modifiers)
    ) {
      return $this->emitReadonlyProperties($result, $property);
    }
    parent::emitProperty($result, $property);
  }
}