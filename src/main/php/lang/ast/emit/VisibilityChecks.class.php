<?php namespace lang\ast\emit;

use lang\ast\Code;

trait VisibilityChecks {

  private function private($name, $access) {
    return new Code(
      '$scope= debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]["class"] ?? null;'.
      'if (__CLASS__ !== $scope && \\lang\\VirtualProperty::class !== $scope)'.
      'throw new \\Error("Cannot '.$access.' property ".__CLASS__."::\$'.$name.' from ".($scope ? "scope ".$scope : "global scope"));'
    );
  }

  private function protected($name, $access) {
    return new Code(
      '$scope= debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]["class"] ?? null;'.
      'if (__CLASS__ !== $scope && !is_subclass_of($scope, __CLASS__) && \\lang\\VirtualProperty::class !== $scope)'.
      'throw new \\Error("Cannot '.$access.' property ".__CLASS__."::\$'.$name.' from ".($scope ? "scope ".$scope : "global scope"));'
    );
  }
}