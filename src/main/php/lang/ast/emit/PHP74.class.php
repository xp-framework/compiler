<?php namespace lang\ast\emit;

/**
 * PHP 7.4 syntax
 *
 * @see  https://wiki.php.net/rfc#php_74
 */
class PHP74 extends PHP {
  use PHP72Literals;
  use CallablesAsClosures;
  use RewriteBlockLambdaExpressions, RewriteClassOnObjects, RewriteExplicitOctals, RewriteEnums;

}