<?php namespace lang\ast\emit;

/**
 * Rewrites enums
 *
 * @see  https://wiki.php.net/rfc/enumerations
 */
trait RewriteEnums {

  protected function emitEnumCase($result, $case) {
    $result->out->write('public static $'.$case->name.';');
  }

  protected function emitEnum($result, $enum) {
    array_unshift($result->type, $enum);
    array_unshift($result->meta, []);
    $result->locals= [[], []];

    $result->out->write('final class '.$this->declaration($enum->name).' implements \\'.($enum->base ? 'BackedEnum' : 'UnitEnum'));
    $enum->implements && $result->out->write(', '.implode(', ', $enum->implements));
    $result->out->write('{');

    $cases= [];
    foreach ($enum->body as $member) {
      if ($member->is('enumcase')) $cases[]= $member;
      $this->emitOne($result, $member);
    }

    // Constructors
    if ($enum->base) {
      $result->out->write('public $name, $value;');
      $result->out->write('private static $values= [];');
      $result->out->write('private function __construct($name, $value) {
        $this->name= $name;
        $this->value= $value;
        self::$values[$value]= $this;
      }');
      $result->out->write('public static function tryFrom($value) {
        return self::$values[$value] ?? null;
      }');
      $result->out->write('public static function from($value) {
        if ($r= self::$values[$value] ?? null) return $r;
        throw new \Error(\util\Objects::stringOf($value)." is not a valid backing value for enum \"".self::class."\"");
      }');
    } else {
      $result->out->write('public $name;');
      $result->out->write('private function __construct($name) {
        $this->name= $name;
      }');
    }

    // Prevent cloning enums
    $result->out->write('public function __clone() {
      throw new \Error("Trying to clone an uncloneable object of class ".self::class);
    }');

    // Enum cases
    $result->out->write('public static function cases() { return [');
    foreach ($cases as $case) {
      $result->out->write('self::$'.$case->name.', ');
    }
    $result->out->write(']; }');

    // Initializations
    $result->out->write('static function __init() {');
    if ($enum->base) {
      foreach ($cases as $case) {
        $result->out->write('self::$'.$case->name.'= new self("'.$case->name.'", ');
        $this->emitOne($result, $case->expression);
        $result->out->write(');');
      }
    } else {
      foreach ($cases as $case) {
        $result->out->write('self::$'.$case->name.'= new self("'.$case->name.'");');
      }
    }
    $this->emitInitializations($result, $result->locals[0]);
    $this->emitMeta($result, $enum->name, $enum->annotations, $enum->comment);
    $result->out->write('}} '.$enum->name.'::__init();');
    array_shift($result->type);
  }
}