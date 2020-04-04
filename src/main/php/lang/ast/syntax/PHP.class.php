<?php namespace lang\ast\syntax;

use lang\ast\nodes\{
  Annotations,
  ArrayLiteral,
  Block,
  Braced,
  BreakStatement,
  CaseLabel,
  CastExpression,
  CatchStatement,
  ClassDeclaration,
  ClosureExpression,
  Constant,
  ContinueStatement,
  DoLoop,
  EchoStatement,
  ForLoop,
  ForeachLoop,
  FunctionDeclaration,
  GotoStatement,
  IfStatement,
  InstanceExpression,
  InstanceOfExpression,
  InterfaceDeclaration,
  InvokeExpression,
  Label,
  LambdaExpression,
  Literal,
  Method,
  NamespaceDeclaration,
  NewClassExpression,
  NewExpression,
  NullSafeInstanceExpression,
  OffsetExpression,
  Parameter,
  Property,
  ReturnStatement,
  ScopeExpression,
  Signature,
  Start,
  StaticLocals,
  SwitchStatement,
  TernaryExpression,
  ThrowExpression,
  ThrowStatement,
  TraitDeclaration,
  TryStatement,
  UnpackExpression,
  UseExpression,
  UseStatement,
  Variable,
  WhileLoop,
  YieldExpression,
  YieldFromExpression
};
use lang\ast\{ArrayType, FunctionType, Language, MapType, Token, Type, UnionType};

/**
 * PHP language
 *
 * @see   https://github.com/php/php-langspec
 */
class PHP extends Language {
  private $body= [];

  /** Setup language parser */
  public function __construct() {
    $this->symbol(':');
    $this->symbol(';');
    $this->symbol(',');
    $this->symbol(')');
    $this->symbol(']');
    $this->symbol('}');
    $this->symbol('as');
    $this->symbol('const');
    $this->symbol('(end)');
    $this->symbol('(name)');
    $this->symbol('(literal)');
    $this->symbol('(variable)');

    $this->constant('true', 'true');
    $this->constant('false', 'false');
    $this->constant('null', 'null');

    $this->infixr('??', 30);
    $this->infixr('?:', 30);
    $this->infixr('&&', 30);
    $this->infixr('||', 30);

    $this->infixr('==', 40);
    $this->infixr('===', 40);
    $this->infixr('!=', 40);
    $this->infixr('!==', 40);
    $this->infixr('<', 40);
    $this->infixr('<=', 40);
    $this->infixr('>', 40);
    $this->infixr('>=', 40);
    $this->infixr('<=>', 40);

    $this->infix('+', 50);
    $this->infix('-', 50);
    $this->infix('&', 50);
    $this->infix('|', 50);
    $this->infix('^', 50);
    $this->suffix('++', 50);
    $this->suffix('--', 50);

    $this->infix('*', 60);
    $this->infix('/', 60);
    $this->infix('%', 60);
    $this->infix('.', 60);
    $this->infix('**', 60);

    $this->infixr('<<', 70);
    $this->infixr('>>', 70);

    $this->infix('instanceof', 110, function($parse, $token, $left) {
      $type= $this->expression($parse, 110);
      if ($type instanceof Literal) {
        return new InstanceOfExpression($left, $parse->scope->resolve($type->expression), $token->line);
      } else {
        return new InstanceOfExpression($left, $type, $token->line);
      }
    });

    $this->infix('->', 100, function($parse, $token, $left) {
      if ('{' === $parse->token->value) {
        $parse->forward();
        $expr= $this->expression($parse, 0);
        $parse->expecting('}', 'dynamic member');
      } else {
        $expr= new Literal($parse->token->value, $token->line);
        $parse->forward();
      }

      return new InstanceExpression($left, $expr, $token->line);
    });

    $this->infix('::', 120, function($parse, $token, $left) {
      $scope= $left instanceof Literal ? $parse->scope->resolve($left->expression) : $left;

      if ('variable' === $parse->token->kind) {
        $expr= new Variable($parse->token->value, $parse->token->line);
      } else if ('name' === $parse->token->kind) {
        $expr= new Literal($parse->token->value, $parse->token->line);
      } else {
        $parse->expecting('name or variable', '::');
        $expr= null;
      }

      $parse->forward();
      if ('(' === $parse->token->value) {
        $parse->expecting('(', 'invoke expression');
        $arguments= $this->expressions($parse);
        $parse->expecting(')', 'invoke expression');
        $expr= new InvokeExpression($expr, $arguments, $token->line);
      }

      return new ScopeExpression($scope, $expr, $token->line);
    });

    $this->infix('(', 100, function($parse, $token, $left) {
      $arguments= $this->expressions($parse);
      $parse->expecting(')', 'invoke expression');
      return new InvokeExpression($left, $arguments, $token->line);
    });

    $this->infix('[', 100, function($parse, $token, $left) {
      if (']' === $parse->token->value) {
        $expr= null;
        $parse->forward();
      } else {
        $expr= $this->expression($parse, 0);
        $parse->expecting(']', 'offset access');
      }

      return new OffsetExpression($left, $expr, $token->line);
    });

    $this->infix('{', 100, function($parse, $token, $left) {
      $parse->warn('Deprecated curly braces use as offset');
      $expr= $this->expression($parse, 0);
      $parse->expecting('}', 'offset');
      return new OffsetExpression($left, $expr, $token->line);
    });

    $this->infix('?', 80, function($parse, $token, $left) {
      $when= $this->expression($parse, 0);
      $parse->expecting(':', 'ternary');
      $else= $this->expression($parse, 0);
      return new TernaryExpression($left, $when, $else, $token->line);
    });

    $this->prefix('@', 90);
    $this->prefix('&', 90);
    $this->prefix('!', 90);
    $this->prefix('~', 90);
    $this->prefix('+', 90);
    $this->prefix('-', 90);
    $this->prefix('++', 90);
    $this->prefix('--', 90);
    $this->prefix('clone', 90);

    $this->assignment('=');
    $this->assignment('&=');
    $this->assignment('|=');
    $this->assignment('^=');
    $this->assignment('+=');
    $this->assignment('-=');
    $this->assignment('*=');
    $this->assignment('/=');
    $this->assignment('.=');
    $this->assignment('**=');
    $this->assignment('>>=');
    $this->assignment('<<=');
    $this->assignment('??=');

    // This is ambiguous:
    //
    // - An arrow function `($a) ==> $a + 1`
    // - An expression surrounded by parentheses `($a ?? $b)->invoke()`;
    // - A cast `(int)$a` or `(int)($a / 2)`.
    //
    // Resolve by looking ahead after the closing ")"
    $this->prefix('(', 0, function($parse, $token) {
      static $types= [
        '<'        => true,
        '>'        => true,
        ','        => true,
        '?'        => true,
        ':'        => true
      ];

      $skipped= [$token, $parse->token];
      $cast= true;
      $level= 1;
      while ($level > 0 && null !== $parse->token->value) {
        if ('(' === $parse->token->value) {
          $level++;
        } else if (')' === $parse->token->value) {
          $level--;
        } else if ('name' !== $parse->token->kind && !isset($types[$parse->token->value])) {
          $cast= false;
        }
        $parse->forward();
        $skipped[]= $parse->token;
      }
      $parse->queue= array_merge($skipped, $parse->queue);

      if ($cast && ('operator' !== $parse->token->kind || '(' === $parse->token->value || '[' === $parse->token->value)) {
        $parse->forward();
        $parse->expecting('(', 'cast');
        $type= $this->type0($parse, false);
        $parse->expecting(')', 'cast');
        return new CastExpression($type, $this->expression($parse, 0), $token->line);
      } else {
        $parse->forward();
        $parse->expecting('(', 'braced');
        $expr= $this->expression($parse, 0);
        $parse->expecting(')', 'braced');
        return new Braced($expr, $token->line);
      }
    });

    $this->prefix('[', 0, function($parse, $token) {
      $values= [];
      while (']' !== $parse->token->value) {
        $expr= $this->expression($parse, 0);

        if ('=>' === $parse->token->value) {
          $parse->forward();
          $values[]= [$expr, $this->expression($parse, 0)];
        } else {
          $values[]= [null, $expr];
        }

        if (']' === $parse->token->value) {
          break;
        } else {
          $parse->expecting(',', 'array literal');
        }
      }

      $parse->expecting(']', 'array literal');
      return new ArrayLiteral($values, $token->line);
    });

    $this->prefix('new', 0, function($parse, $token) {
      if ('(' === $parse->token->value) {
        $parse->expecting('(', 'new type');
        $type= $this->expression($parse, 0);
        $parse->expecting(')', 'new type');
      } else if ('variable' === $parse->token->kind) {
        $type= '$'.$parse->token->value;
        $parse->forward();
      } else if ('class' === $parse->token->value) {
        $type= null;
        $parse->forward();
      } else {
        $type= $parse->scope->resolve($parse->token->value);
        $parse->forward();
      }

      $parse->expecting('(', 'new arguments');
      $arguments= $this->expressions($parse);
      $parse->expecting(')', 'new arguments');

      if (null === $type) {
        return new NewClassExpression($this->clazz($parse, null), $arguments, $token->line);
      } else {
        return new NewExpression($type, $arguments, $token->line);
      }
    });

    $this->prefix('yield', 0, function($parse, $token) {
      if (';' === $parse->token->value) {
        return new YieldExpression(null, null, $token->line);
      } else if ('from' === $parse->token->value) {
        $parse->forward();
        return new YieldFromExpression($this->expression($parse, 0), $token->line);
      } else {
        $expr= $this->expression($parse, 0);
        if ('=>' === $parse->token->value) {
          $parse->forward();
          return new YieldExpression($expr, $this->expression($parse, 0), $token->line);
        } else {
          return new YieldExpression(null, $expr, $token->line);
        }
      }
    });

    $this->prefix('...', 0, function($parse, $token) {
      return new UnpackExpression($this->expression($parse, 0), $token->line);
    });

    $this->prefix('fn', 0, function($parse, $token) {
      $signature= $this->signature($parse);
      $parse->expecting('=>', 'fn');

      if ('{' === $parse->token->value) {
        $parse->expecting('{', 'fn');
        $statements= $this->statements($parse);
        $parse->expecting('}', 'fn');
      } else {
        $statements= $this->expression($parse, 0);
      }

      return new LambdaExpression($signature, $statements, $token->line);
    });

    $this->prefix('function', 0, function($parse, $token) {

      // Closure `$a= function() { ... };` vs. declaration `function a() { ... }`;
      // the latter explicitely becomes a statement by pushing a semicolon.
      if ('(' === $parse->token->value) {
        $signature= $this->signature($parse);

        if ('use' === $parse->token->value) {
          $parse->forward();
          $parse->forward();
          $use= [];
          while (')' !== $parse->token->value) {
            if ('&' === $parse->token->value) {
              $parse->forward();
              $use[]= '&$'.$parse->token->value;
            } else {
              $use[]= '$'.$parse->token->value;
            }
            $parse->forward();
            if (')' === $parse->token->value) break;
            $parse->expecting(',', 'use list');
          }
          $parse->expecting(')', 'closure');
        } else {
          $use= null;
        }

        $parse->expecting('{', 'function');
        $statements= $this->statements($parse);
        $parse->expecting('}', 'function');

        return new ClosureExpression($signature, $use, $statements, $token->line);
      } else {
        $name= $parse->token->value;
        $parse->forward();
        $signature= $this->signature($parse);

        $parse->expecting('{', 'function');
        $statements= $this->statements($parse);
        $parse->expecting('}', 'function');

        $parse->queue= [$parse->token];
        $parse->token= new Token($this->symbol(';'));
        return new FunctionDeclaration($name, $signature, $statements, $token->line);
      }
    });

    $this->prefix('static', 0, function($parse, $token) {
      if ('variable' === $parse->token->kind) {
        $init= [];
        while (';' !== $parse->token->value) {
          $variable= $parse->token->value;
          $parse->forward();

          if ('=' === $parse->token->value) {
            $parse->forward();
            $init[$variable]= $this->expression($parse, 0);
          } else {
            $init[$variable]= null;
          }

          if (',' === $parse->token->value) {
            $parse->forward();
          }
        }
        return new StaticLocals($init, $token->line);
      }
      return new Literal($token->value, $token->line);
    });

    $this->prefix('goto', 0, function($parse, $token) {
      $label= $parse->token->value;
      $parse->forward();
      return new GotoStatement($label, $token->line);
    });

    $this->prefix('(variable)', 0, function($parse, $token) {
      return new Variable($token->value, $token->line);
    });

    $this->prefix('(literal)', 0, function($parse, $token) {
      return new Literal($token->value, $token->line);
    });

    $this->prefix('(name)', 0, function($parse, $token) {
      return new Literal($token->value, $token->line);
    });

    $this->stmt('(name)', function($parse, $token) {

      // Solve ambiguity between goto-labels and other statements
      if (':' === $parse->token->value) {
        $node= new Label($token->value, $token->line);
      } else {
        $parse->queue[]= $parse->token;
        $parse->token= $token;
        $node= $this->expression($parse, 0);
      }
      $parse->forward();
      return $node;
    });

    $this->stmt('<?', function($parse, $token) {
      $syntax= $parse->token->value;
      $parse->forward();
      return new Start($syntax, $token->line);
    });

    $this->stmt('{', function($parse, $token) {
      $statements= $this->statements($parse);
      $parse->expecting('}', 'block');
      return new Block($statements, $token->line);
    });

    $this->prefix('echo', 0, function($parse, $token) {
      return new EchoStatement($this->expressions($parse, ';'), $token->line);
    });

    $this->stmt('namespace', function($parse, $token) {
      $name= $parse->token->value;
      $parse->forward();
      $parse->expecting(';', 'namespace');
      $parse->scope->package($name);
      return new NamespaceDeclaration($name, $token->line);
    });

    $this->stmt('use', function($parse, $token) {
      if ('function' === $parse->token->value) {
        $type= 'function';
        $parse->forward();
      } else if ('const' === $parse->token->value) {
        $type= 'const';
        $parse->forward();
      } else {
        $type= null;  // class, interface or trait
      }

      $import= $parse->token->value;
      $parse->forward();

      if ('{' === $parse->token->value) {
        $names= [];
        $parse->forward();
        while ('}' !== $parse->token->value) {
          $class= $import.$parse->token->value;

          $parse->forward();
          if ('as' === $parse->token->value) {
            $parse->forward();
            $names[$class]= $parse->token->value;
            $parse->scope->import($parse->token->value);
            $parse->forward();
          } else {
            $names[$class]= null;
            $parse->scope->import($class);
          }

          if (',' === $parse->token->value) {
            $parse->forward();
          } else if ('}' === $parse->token->value) {
            break;
          } else {
            $this->expecting(', or }', 'use');
            break;
          }
        }
        $parse->forward();
      } else if ('as' === $parse->token->value) {
        $parse->forward();
        $names= [$import => $parse->token->value];
        $parse->scope->import($import, $parse->token->value);
        $parse->forward();
      } else {
        $names= [$import => null];
        $parse->scope->import($import);
      }

      $parse->expecting(';', 'use');
      return new UseStatement($type, $names, $token->line);
    });

    $this->stmt('if', function($parse, $token) {
      $parse->expecting('(', 'if');
      $condition= $this->expression($parse, 0);
      $parse->expecting(')', 'if');
      $when= $this->block($parse);
      if ('else' === $parse->token->value) {
        $parse->forward();
        $otherwise= $this->block($parse);
      } else {
        $otherwise= null;
      }

      return new IfStatement($condition, $when, $otherwise, $token->line);
    });

    $this->stmt('switch', function($parse, $token) {
      $parse->expecting('(', 'switch');
      $condition= $this->expression($parse, 0);
      $parse->expecting(')', 'switch');

      $cases= [];
      $parse->expecting('{', 'switch');
      while ('}' !== $parse->token->value) {
        if ('default' === $parse->token->value) {
          $parse->forward();
          $parse->expecting(':', 'switch');
          $cases[]= new CaseLabel(null, [], $parse->token->line);
        } else if ('case' === $parse->token->value) {
          $parse->forward();

          if ('name' === $parse->token->kind) {
            $expr= new Literal($parse->token->value, $parse->token->line);
            $parse->forward();
          } else {
            $expr= $this->expression($parse, 0);
          }
          $parse->expecting(':', 'switch');
          $cases[]= new CaseLabel($expr, [], $parse->token->line);
        } else {
          $cases[sizeof($cases) - 1]->body[]= $this->statement($parse);
        }
      }
      $parse->forward();

      return new SwitchStatement($condition, $cases, $token->line);
    });

    $this->stmt('break', function($parse, $token) {
      if (';' === $parse->token->value) {
        $expr= null;
        $parse->forward();
      } else {
        $expr= $this->expression($parse, 0);
        $parse->expecting(';', 'break');
      }

      return new BreakStatement($expr, $token->line);
    });

    $this->stmt('continue', function($parse, $token) {
      if (';' === $parse->token->value) {
        $expr= null;
        $parse->forward();
      } else {
        $expr= $this->expression($parse, 0);
        $parse->expecting(';', 'continue');
      }

      return new ContinueStatement($expr, $token->line);
    });

    $this->stmt('do', function($parse, $token) {
      $block= $this->block($parse);
      $parse->expecting('while', 'do');
      $parse->expecting('(', 'do');
      $expression= $this->expression($parse, 0);
      $parse->expecting(')', 'do');
      $parse->expecting(';', 'do');
      return new DoLoop($expression, $block, $token->line);
    });

    $this->stmt('while', function($parse, $token) {
      $parse->expecting('(', 'while');
      $expression= $this->expression($parse, 0);
      $parse->expecting(')', 'while');
      $block= $this->block($parse);
      return new WhileLoop($expression, $block, $token->line);
    });

    $this->stmt('for', function($parse, $token) {
      $parse->expecting('(', 'for');
      $init= $this->expressions($parse, ';');
      $parse->expecting(';', 'for');
      $cond= $this->expressions($parse, ';');
      $parse->expecting(';', 'for');
      $loop= $this->expressions($parse, ')');
      $parse->expecting(')', 'for');
      $block= $this->block($parse);
      return new ForLoop($init, $cond, $loop, $block, $token->line);
    });

    $this->stmt('foreach', function($parse, $token) {
      $parse->expecting('(', 'foreach');
      $expression= $this->expression($parse, 0);
      $parse->expecting('as', 'foreach');
      $expr= $this->expression($parse, 0);

      if ('=>' === $parse->token->value) {
        $parse->forward();
        $key= $expr;
        $value= $this->expression($parse, 0);
      } else {
        $key= null;
        $value= $expr;
      }

      $parse->expecting(')', 'foreach');
      $block= $this->block($parse);
      return new ForeachLoop($expression, $key, $value, $block, $token->line);
    });

    $this->stmt('throw', function($parse, $token) {
      $expr= $this->expression($parse, 0);
      $parse->expecting(';', 'throw');
      return new ThrowStatement($expr, $token->line);
    });

    $this->stmt('try', function($parse, $token) {
      $parse->expecting('{', 'try');
      $statements= $this->statements($parse);
      $parse->expecting('}', 'try');

      $catches= [];
      while ('catch'  === $parse->token->value) {
        $parse->forward();
        $parse->expecting('(', 'try');

        $types= [];
        while ('name' === $parse->token->kind) {
          $types[]= $parse->scope->resolve($parse->token->value);
          $parse->forward();
          if ('|' !== $parse->token->value) break;
          $parse->forward();
        }

        $variable= $parse->token;
        $parse->forward();
        $parse->expecting(')', 'catch');

        $parse->expecting('{', 'catch');
        $catches[]= new CatchStatement($types, $variable->value, $this->statements($parse), $parse->token->line);
        $parse->expecting('}', 'catch');
      }

      if ('finally' === $parse->token->value) {
        $parse->forward();
        $parse->expecting('{', 'finally');
        $finally= $this->statements($parse);
        $parse->expecting('}', 'finally');
      } else {
        $finally= null;
      }

      return new TryStatement($statements, $catches, $finally, $token->line);
    });

    $this->stmt('return', function($parse, $token) {
      if (';' === $parse->token->value) {
        $expr= null;
        $parse->forward();
      } else {
        $expr= $this->expression($parse, 0);
        $parse->expecting(';', 'return');
      }

      return new ReturnStatement($expr, $token->line);
    });

    $this->stmt('abstract', function($parse, $token) {
      $type= $this->statement($parse);
      $type->modifiers[]= 'abstract';
      return $type;
    });

    $this->stmt('final', function($parse, $token) {
      $type= $this->statement($parse);
      $type->modifiers[]= 'final';
      return $type;
    });

    $this->stmt('<<', function($parse, $token) {
      $parse->scope->annotations= $this->annotations($parse, 'annotations');

      return new Annotations($parse->scope->annotations, $token->line);
    });

    $this->stmt('#[', function($parse, $token) {
      $parse->scope->annotations= $this->meta($parse, 'annotations')[DETAIL_ANNOTATIONS];

      return new Annotations($parse->scope->annotations, $token->line);
    });

    $this->stmt('class', function($parse, $token) {
      $type= $parse->scope->resolve($parse->token->value);
      $parse->forward();

      return $this->clazz($parse, $type);
    });

    $this->stmt('interface', function($parse, $token) {
      $type= $parse->scope->resolve($parse->token->value);
      $parse->forward();
      $comment= $parse->comment;
      $parse->comment= null;

      $parents= [];
      if ('extends' === $parse->token->value) {
        $parse->forward();
        do {
          $parents[]= $parse->scope->resolve($parse->token->value);
          $parse->forward();
          if (',' === $parse->token->value) {
            $parse->forward();
          } else if ('{' === $parse->token->value) {
            break;
          } else {
            $parse->expecting(', or {', 'interface parents');
          }
        } while (null !== $parse->token->value);
      }

      $parse->expecting('{', 'interface');
      $body= $this->typeBody($parse);
      $parse->expecting('}', 'interface');

      $decl= new InterfaceDeclaration([], $type, $parents, $body, $parse->scope->annotations, $comment, $token->line);
      $parse->scope->annotations= [];
      return $decl;
    });

    $this->stmt('trait', function($parse, $token) {
      $type= $parse->scope->resolve($parse->token->value);
      $parse->forward();
      $comment= $parse->comment;
      $parse->comment= null;

      $parse->expecting('{', 'trait');
      $body= $this->typeBody($parse);
      $parse->expecting('}', 'trait');

      $decl= new TraitDeclaration([], $type, $body, $parse->scope->annotations, $comment, $token->line);
      $parse->scope->annotations= [];
      return $decl;
    });

    $this->body('use', function($parse, &$body, $annotations, $modifiers) {
      $line= $parse->token->line;

      $parse->forward();
      $types= [];
      do {
        $types[]= $parse->scope->resolve($parse->token->value);
        $parse->forward();
        if (',' === $parse->token->value) {
          $parse->forward();
          continue;
        } else {
          break;
        }
      } while ($parse->token->value);

      $aliases= [];
      if ('{' === $parse->token->value) {
        $parse->forward();
        while ('}' !== $parse->token->value) {
          $method= $parse->token->value;
          $parse->forward();
          if ('::' === $parse->token->value) {
            $parse->forward();
            $method= $parse->scope->resolve($method).'::'.$parse->token->value;
            $parse->forward();
          }
          $parse->expecting('as', 'use');
          $alias= $parse->token->value;
          $parse->forward();
          $parse->expecting(';', 'use');
          $aliases[$method]= $alias;
        }
        $parse->expecting('}', 'use');
      } else {
        $parse->expecting(';', 'use');
      }

      $body[]= new UseExpression($types, $aliases, $line);
    });

    $this->body('const', function($parse, &$body, $annotations, $modifiers) {
      $parse->forward();

      $type= null;
      while (';' !== $parse->token->value) {
        $line= $parse->token->line;
        $first= $parse->token;
        $parse->forward();

        // Untyped `const T = 5` vs. typed `const int T = 5`
        if ('=' === $parse->token->value) {
          $name= $first->value;
        } else {
          $parse->queue[]= $first;
          $parse->queue[]= $parse->token;
          $parse->token= $first;

          $type= $this->type($parse, false);
          $parse->forward();
          $name= $parse->token->value;
          $parse->forward();
        }

        if (isset($body[$name])) {
          $parse->raise('Cannot redeclare constant '.$name);
        }

        $parse->expecting('=', 'const');
        $body[$name]= new Constant($modifiers, $name, $type, $this->expression($parse, 0), $line);
        if (',' === $parse->token->value) {
          $parse->forward();
        }
      }
      $parse->expecting(';', 'constant declaration');
    });

    $this->body('@variable', function($parse, &$body, $meta, $modifiers) {
      $this->properties($parse, $body, $meta, $modifiers, null);
    });

    $this->body('function', function($parse, &$body, $meta, $modifiers) {
      $line= $parse->token->line;
      $comment= $parse->comment;
      $parse->comment= null;

      $parse->forward();
      $name= $parse->token->value;
      $lookup= $name.'()';
      if (isset($body[$lookup])) {
        $parse->raise('Cannot redeclare method '.$lookup);
      }

      $parse->forward();
      $signature= $this->signature($parse, isset($meta[DETAIL_TARGET_ANNO]) ? $meta[DETAIL_TARGET_ANNO] : []);

      if ('{' === $parse->token->value) {          // Regular body
        $parse->forward();
        $statements= $this->statements($parse);
        $parse->expecting('}', 'method declaration');
      } else if (';' === $parse->token->value) {   // Abstract or interface method
        $statements= null;
        $parse->expecting(';', 'method declaration');
      } else {
        $parse->expecting('{ or ;', 'method declaration');
      }

      $body[$lookup]= new Method(
        $modifiers,
        $name,
        $signature,
        $statements,
        isset($meta[DETAIL_ANNOTATIONS]) ? $meta[DETAIL_ANNOTATIONS] : [],
        $comment,
        $line
      );
    });
  }

  private function type($parse, $optional= true) {
    $t= [];
    do {
      $t[]= $this->type0($parse, $optional);
      if ('|' === $parse->token->value) {
        $parse->forward();
        continue;
      }
      return 1 === sizeof($t) ? $t[0] : new UnionType($t);
    } while (true);
  }

  private function type0($parse, $optional) {
    if ('?' === $parse->token->value) {
      $parse->forward();
      $type= '?'.$parse->scope->resolve($parse->token->value);
      $parse->forward();
    } else if ('(' === $parse->token->value) {
      $parse->forward();
      $type= $this->type($parse, false);
      $parse->forward();
      return $type;
    } else if ('name' === $parse->token->kind && 'function' === $parse->token->value) {
      $parse->forward();
      $parse->expecting('(', 'type');
      $signature= [];
      if (')' !== $parse->token->value) do {
        $signature[]= $this->type($parse, false);
        if (',' === $parse->token->value) {
          $parse->forward();
        } else if (')' === $parse->token->value) {
          break;
        } else {
          $parse->expecting(', or )', 'function type');
        }
      } while (null !== $parse->token->value);
      $parse->expecting(')', 'type');
      $parse->expecting(':', 'type');
      return new FunctionType($signature, $this->type($parse, false));
    } else if ('name' === $parse->token->kind) {
      $type= $parse->scope->resolve($parse->token->value);
      $parse->forward();
    } else if ($optional) {
      return null;
    } else {
      $parse->expecting('type name', 'type');
      return null;
    }

    if ('<' === $parse->token->value) {
      $parse->forward();
      $components= [];
      do {
        $components[]= $this->type($parse, false);
        if (',' === $parse->token->value) {
          $parse->forward();
        } else if ('>' === $parse->token->symbol->id) {
          break;
        } else if ('>>' === $parse->token->value) {
          $parse->queue[]= $parse->token= new Token(self::symbol('>'));
          break;
        }
      } while (true);
      $parse->expecting('>', 'type');

      if ('array' === $type) {
        return 1 === sizeof($components) ? new ArrayType($components[0]) : new MapType($components[0], $components[1]);
      } else {
        return new GenericType($type, $components);
      }
    } else {
      return new Type($type);
    }
  }

  private function properties($parse, &$body, $meta, $modifiers, $type) {
    $comment= $parse->comment;
    $parse->comment= null;
    $annotations= isset($meta[DETAIL_ANNOTATIONS]) ? $meta[DETAIL_ANNOTATIONS] : [];

    while (';' !== $parse->token->value) {
      $line= $parse->token->line;

      // Untyped `$a` vs. typed `int $a`
      if ('variable' === $parse->token->kind) {
        $name= $parse->token->value;
      } else {
        $type= $this->type($parse, false);
        $name= $parse->token->value;
      }

      $lookup= '$'.$name;
      if (isset($body[$lookup])) {
        $parse->raise('Cannot redeclare property '.$lookup);
      }

      $parse->forward();
      if ('=' === $parse->token->value) {
        $parse->forward();
        $expr= $this->expression($parse, 0);
      } else {
        $expr= null;
      }
      $body[$lookup]= new Property($modifiers, $name, $type, $expr, $annotations, $comment, $line);

      if (',' === $parse->token->value) {
        $parse->forward();
      }
    }
    $parse->expecting(';', 'field declaration');
  }

  /** Parses Hacklang-style annotations (<<test>>) */
  private function annotations($parse, $context) {
    $annotations= [];
    do {
      $name= $parse->token->value;
      $parse->forward();

      if ('(' === $parse->token->value) {
        $parse->expecting('(', $context);
        $annotations[$name]= $this->expression($parse, 0);
        $parse->expecting(')', $context);
      } else {
        $annotations[$name]= null;
      }

      if (',' === $parse->token->value) {
        $parse->forward();
        continue;
      } else if ('>>' === $parse->token->value) {
        break;
      } else {
        $parse->expecting(', or >>', $context);
      }
    } while (null !== $parse->token->value);

    $parse->expecting('>>', $context);
    return $annotations;
  }

  /** Parses XP-style annotations (#[@test]) */
  private function meta($parse, $context) {
    $meta= [DETAIL_ANNOTATIONS => [], DETAIL_TARGET_ANNO => []];
    do {
      $parse->expecting('@', $context);

      if ('variable' === $parse->token->kind) {
        $param= $parse->token->value;
        $parse->forward();
        $parse->expecting(':', $context);
        $a= &$meta[DETAIL_TARGET_ANNO][$param];
      } else {
        $a= &$meta[DETAIL_ANNOTATIONS];
      }

      $name= $parse->token->value;
      $parse->forward();

      if ('(' === $parse->token->value) {
        $parse->expecting('(', $context);

        if ('name' === $parse->token->kind) {
          $token= $parse->token;
          $parse->forward();
          $pairs= '=' === $parse->token->value;

          $parse->queue[]= $parse->token;
          $parse->token= $token;

          if ($pairs) {
            $line= $parse->token->line;
            $values= [];
            do {
              $key= $parse->token->value;
              $parse->warn('Use of deprecated annotation key/value pair "'.$key.'"', $context);
              $parse->forward();
              $parse->expecting('=', $context);

              $values[]= [new Literal("'".$key."'"), $this->expression($parse, 0)];

              if (',' === $parse->token->value) {
                $parse->forward();
                continue;
              }
              break;
            } while (null !== $parse->token->value); 
            $a[$name]= new ArrayLiteral($values, $line);
          } else {
            $a[$name]= $this->expression($parse, 0);
          }
        } else {
          $a[$name]= $this->expression($parse, 0);
        }
        $parse->expecting(')', $context);
      } else {
        $a[$name]= null;
      }

      if (',' === $parse->token->value) {
        $parse->forward();
        continue;
      } else if (']' === $parse->token->value) {
        break;
      } else {
        $parse->expecting(', or ]', $context);
      }
    } while (null !== $parse->token->value);

    $parse->expecting(']', $context);
    return $meta;
  }

  private function parameters($parse, $target) {
    static $promotion= ['private' => true, 'protected' => true, 'public' => true];

    $parameters= [];
    while (')' !== $parse->token->value) {
      if ('<<' === $parse->token->value) {
        $parse->forward();
        $annotations= $this->annotations($parse, 'parameter annotation');
      } else {
        $annotations= [];
      }

      if ('name' === $parse->token->kind && isset($promotion[$parse->token->value])) {
        $promote= $parse->token->value;
        $parse->forward();
      } else {
        $promote= null;
      }

      $type= $this->type($parse);

      if ('...' !== $parse->token->value) {
        $variadic= false;
      } else if ($promote) {
        $parse->raise('Variadic parameters cannot be promoted', 'parameters');
        $variadic= true;
        $parse->forward();
      } else {
        $variadic= true;
        $parse->forward();
      }

      if ('&' === $parse->token->value) {
        $byref= true;
        $parse->forward();
      } else {
        $byref= false;
      }

      $name= $parse->token->value;
      if (isset($target[$name])) $annotations= array_merge($annotations, $target[$name]);
      $parse->forward();

      $default= null;
      if ('=' === $parse->token->value) {
        $parse->forward();
        $default= $this->expression($parse, 0);
      }
      $parameters[]= new Parameter($name, $type, $default, $byref, $variadic, $promote, $annotations);

      if (')' === $parse->token->value) {
        break;
      } else if (',' === $parse->token->value) {
        $parse->forward();
        continue;
      } else {
        $parse->expecting(',', 'parameter list');
        break;
      }
    }
    return $parameters;
  }

  public function body($id, $func) {
    $this->body[$id]= $func->bindTo($this, static::class);
  }

  public function typeBody($parse) {
    static $modifier= [
      'private'   => true,
      'protected' => true,
      'public'    => true,
      'static'    => true,
      'final'     => true,
      'abstract'  => true
    ];

    $body= [];
    $modifiers= [];
    $meta= [];
    while ('}' !== $parse->token->value) {
      if (isset($modifier[$parse->token->value])) {
        $modifiers[]= $parse->token->value;
        $parse->forward();
      } else if ($f= $this->body[$parse->token->value] ?? $this->body['@'.$parse->token->kind] ?? null) {
        $f($parse, $body, $meta, $modifiers);
        $modifiers= [];
        $meta= [];
      } else if ('<<' === $parse->token->value) {
        $parse->forward();
        $meta= [DETAIL_ANNOTATIONS => $this->annotations($parse, 'member annotations')];
      } else if ('#[' === $parse->token->value) {
        $parse->forward();
        $meta= $this->meta($parse, 'member annotations');
      } else if ($type= $this->type($parse)) {
        $this->properties($parse, $body, $meta, $modifiers, $type);
        $modifiers= [];
        $meta= [];
      } else {
        $parse->raise(sprintf(
          'Expected a type, modifier, property, annotation, method or "}", have "%s"',
          $parse->token->symbol->id
        ));
        $parse->forward();
        if (null === $parse->token->value) break;
      }
    }
    return $body;
  }

  public function signature($parse, $annotations= []) {
    $parse->expecting('(', 'signature');
    $parameters= $this->parameters($parse, $annotations);
    $parse->expecting(')', 'signature');

    if (':' === $parse->token->value) {
      $parse->forward();
      $return= $this->type($parse);
    } else {
      $return= null;
    }

    return new Signature($parameters, $return);
  }

   public function block($parse) {
    if ('{'  === $parse->token->value) {
      $parse->forward();
      $block= $this->statements($parse);
      $parse->expecting('}', 'block');
      return $block;
    } else {
      return [$this->statement($parse)];
    }
  }

  public function clazz($parse, $name, $modifiers= []) {
    $comment= $parse->comment;
    $parse->comment= null;
    $line= $parse->token->line;

    $parent= null;
    if ('extends' === $parse->token->value) {
      $parse->forward();
      $parent= $parse->scope->resolve($parse->token->value);
      $parse->forward();
    }

    $implements= [];
    if ('implements' === $parse->token->value) {
      $parse->forward();
      do {
        $implements[]= $parse->scope->resolve($parse->token->value);
        $parse->forward();
        if (',' === $parse->token->value) {
          $parse->forward();
        } else if ('{' === $parse->token->value) {
          break;
        } else {
          $parse->expecting(', or {', 'interfaces list');
        }
      } while (null !== $parse->token->value);
    }

    $parse->expecting('{', 'class');
    $body= $this->typeBody($parse);
    $parse->expecting('}', 'class');

    $return= new ClassDeclaration($modifiers, $name, $parent, $implements, $body, $parse->scope->annotations, $comment, $line);
    $parse->scope->annotations= [];
    return $return;
  }

  public function expressions($parse, $end= ')') {
    $arguments= [];
    while ($end !== $parse->token->value) {
      $arguments[]= $this->expression($parse, 0);
      if (',' === $parse->token->value) {
        $parse->forward();
      } else if ($end === $parse->token->value) {
        break;
      } else {
        $parse->expecting($end.' or ,', 'argument list');
        break;
      }
    }
    return $arguments;
  }
}