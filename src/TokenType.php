<?php

declare(strict_types = 1);

namespace Graphpinator\Tokenizer;

final class TokenType
{
    use \Nette\StaticClass;

    public const NEWLINE = 'newline';
    public const COMMENT = '#';
    public const COMMA = ',';
    // lexical
    public const NAME = 'name';
    public const VARIABLE = '$';
    public const DIRECTIVE = '@';
    public const INT = 'int literal';
    public const FLOAT = 'float literal';
    public const STRING = 'string literal';
    // keywords
    public const NULL = 'null';
    public const TRUE = 'true';
    public const FALSE = 'false';
    public const QUERY = OperationType::QUERY;
    public const MUTATION = OperationType::MUTATION;
    public const SUBSCRIPTION = OperationType::SUBSCRIPTION;
    public const FRAGMENT = 'fragment';
    public const ON = 'on'; // type condition
    // type system keywords
    public const SCHEMA = 'schema';
    public const TYPE = 'type';
    public const INTERFACE = 'interface';
    public const UNION = 'union';
    public const INPUT = 'input';
    public const ENUM = 'enum';
    public const SCALAR = 'scalar';
    public const IMPLEMENTS = 'implements';
    public const REPEATABLE = 'repeatable';
    // punctators
    public const AMP = '&'; // implements
    public const PIPE = '|'; // union
    public const EXCL = '!'; // not null
    public const PAR_O = '('; // argument, variable, directive
    public const PAR_C = ')';
    public const CUR_O = '{'; // selection set
    public const CUR_C = '}';
    public const SQU_O = '['; // list
    public const SQU_C = ']';
    public const ELLIP = '...'; // fragment spread
    public const COLON = ':'; // argument, variable, directive, field alias
    public const EQUAL = '='; // default value

    public const IGNORABLE = [
        self::COMMA => true,
        self::COMMENT => true,
        self::NEWLINE => true,
    ];
}
