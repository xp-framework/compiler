<?php namespace lang\ast\emit;

/**
 * PHP 7.1 syntax
 *
 * @see  https://wiki.php.net/rfc#php_71
 */
class PHP71 extends PHP {
  use PHP71Literals;
  use OmitPropertyTypes, CallablesAsClosures;
  use RewriteNullCoalesceAssignment, RewriteLambdaExpressions, RewriteClassOnObjects, RewriteExplicitOctals, RewriteEnums;

}