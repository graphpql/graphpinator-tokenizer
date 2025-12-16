<?php

declare(strict_types = 1);

namespace Graphpinator\Tokenizer\Tests\Unit;

use Graphpinator\Common\Location;
use Graphpinator\Source\StringSource;
use Graphpinator\Tokenizer\Exception\InvalidEllipsis;
use Graphpinator\Tokenizer\Exception\MissingDirectiveName;
use Graphpinator\Tokenizer\Exception\MissingVariableName;
use Graphpinator\Tokenizer\Exception\NumericLiteralFollowedByName;
use Graphpinator\Tokenizer\Exception\NumericLiteralLeadingZero;
use Graphpinator\Tokenizer\Exception\NumericLiteralMalformed;
use Graphpinator\Tokenizer\Exception\NumericLiteralNegativeFraction;
use Graphpinator\Tokenizer\Exception\StringLiteralInvalidEscape;
use Graphpinator\Tokenizer\Exception\StringLiteralNewLine;
use Graphpinator\Tokenizer\Exception\StringLiteralWithoutEnd;
use Graphpinator\Tokenizer\Exception\UnknownSymbol;
use Graphpinator\Tokenizer\Token;
use Graphpinator\Tokenizer\TokenType;
use Graphpinator\Tokenizer\Tokenizer;
use PHPUnit\Framework\TestCase;

final class TokenizerTest extends TestCase
{
    public static function skipDataProvider() : array
    {
        return [
            [
                'query { field(argName: ["str", "str", true, false, null]) }',
                [
                    new Token(TokenType::QUERY, new Location(1, 1)),
                    new Token(TokenType::CUR_O, new Location(1, 7)),
                    new Token(TokenType::NAME, new Location(1, 9), 'field'),
                    new Token(TokenType::PAR_O, new Location(1, 14)),
                    new Token(TokenType::NAME, new Location(1, 15), 'argName'),
                    new Token(TokenType::COLON, new Location(1, 22)),
                    new Token(TokenType::SQU_O, new Location(1, 24)),
                    new Token(TokenType::STRING, new Location(1, 25), 'str'),
                    new Token(TokenType::STRING, new Location(1, 32), 'str'),
                    new Token(TokenType::TRUE, new Location(1, 39)),
                    new Token(TokenType::FALSE, new Location(1, 45)),
                    new Token(TokenType::NULL, new Location(1, 52)),
                    new Token(TokenType::SQU_C, new Location(1, 56)),
                    new Token(TokenType::PAR_C, new Location(1, 57)),
                    new Token(TokenType::CUR_C, new Location(1, 59)),
                ],
            ],
            [
                'query {' . \PHP_EOL .
                    'field1 {' . \PHP_EOL .
                    'innerField' . \PHP_EOL .
                    '}' . \PHP_EOL .
                '}',
                [
                    new Token(TokenType::QUERY, new Location(1, 1)),
                    new Token(TokenType::CUR_O, new Location(1, 7)),
                    new Token(TokenType::NAME, new Location(2, 1), 'field1'),
                    new Token(TokenType::CUR_O, new Location(2, 8)),
                    new Token(TokenType::NAME, new Location(3, 1), 'innerField'),
                    new Token(TokenType::CUR_C, new Location(4, 1)),
                    new Token(TokenType::CUR_C, new Location(5, 1)),
                ],
            ],
            [
                'query {' . \PHP_EOL .
                    'field1 {' . \PHP_EOL .
                        '# this is comment' . \PHP_EOL .
                        'innerField' . \PHP_EOL .
                    '}' . \PHP_EOL .
                '}',
                [
                    new Token(TokenType::QUERY, new Location(1, 1)),
                    new Token(TokenType::CUR_O, new Location(1, 7)),
                    new Token(TokenType::NAME, new Location(2, 1), 'field1'),
                    new Token(TokenType::CUR_O, new Location(2, 8)),
                    new Token(TokenType::NAME, new Location(4, 1), 'innerField'),
                    new Token(TokenType::CUR_C, new Location(5, 1)),
                    new Token(TokenType::CUR_C, new Location(6, 1)),
                ],
            ],
        ];
    }

    public static function noKeywordsDataProvider() : array
    {
        return [
            [
                '... type fragment',
                [
                    new Token(TokenType::ELLIP, new Location(1, 1)),
                    new Token(TokenType::NAME, new Location(1, 5), 'type'),
                    new Token(TokenType::NAME, new Location(1, 10), 'fragment'),
                ],
            ],
        ];
    }

    public static function invalidDataProvider() : array
    {
        return [
            ['"foo', StringLiteralWithoutEnd::class],
            ['""""', StringLiteralWithoutEnd::class],
            ['"""""', StringLiteralWithoutEnd::class],
            ['"""""""', StringLiteralWithoutEnd::class],
            ['"""\\""""', StringLiteralWithoutEnd::class],
            ['"""abc""""', StringLiteralWithoutEnd::class],
            ['"\\1"', StringLiteralInvalidEscape::class],
            ['"\\u12z3"', StringLiteralInvalidEscape::class],
            ['"\\u123"', StringLiteralInvalidEscape::class],
            ['"' . \PHP_EOL . '"', StringLiteralNewLine::class],
            ['123.-1', NumericLiteralNegativeFraction::class],
            ['- 123', NumericLiteralMalformed::class],
            ['123. ', NumericLiteralMalformed::class],
            ['123.1e ', NumericLiteralMalformed::class],
            ['00123', NumericLiteralLeadingZero::class],
            ['00123.123', NumericLiteralLeadingZero::class],
            ['123.1E ', NumericLiteralMalformed::class],
            ['123e ', NumericLiteralMalformed::class],
            ['123E ', NumericLiteralMalformed::class],
            ['123Name', NumericLiteralFollowedByName::class],
            ['123.123Name', NumericLiteralFollowedByName::class],
            ['123.123eName', NumericLiteralMalformed::class],
            ['-.E', NumericLiteralMalformed::class],
            ['>>', UnknownSymbol::class],
            ['123.45.67', InvalidEllipsis::class],
            ['.E', InvalidEllipsis::class],
            ['..', InvalidEllipsis::class],
            ['....', InvalidEllipsis::class],
            ['@ directiveName', MissingDirectiveName::class],
            ['$ variableName', MissingVariableName::class],
        ];
    }

    public static function simpleDataProvider() : array
    {
        return [
            [
                '""',
                [
                    new Token(TokenType::STRING, new Location(1, 1), ''),
                ],
            ],
            [
                '"ěščřžýá"',
                [
                    new Token(TokenType::STRING, new Location(1, 1), 'ěščřžýá'),
                ],
            ],
            [
                '"\\""',
                [
                    new Token(TokenType::STRING, new Location(1, 1), '"'),
                ],
            ],
            [
                '"\\\\"',
                [
                    new Token(TokenType::STRING, new Location(1, 1), '\\'),
                ],
            ],
            [
                '"\\/"',
                [
                    new Token(TokenType::STRING, new Location(1, 1), '/'),
                ],
            ],
            [
                '"\\b"',
                [
                    new Token(TokenType::STRING, new Location(1, 1), "\u{0008}"),
                ],
            ],
            [
                '"\\f"',
                [
                    new Token(TokenType::STRING, new Location(1, 1), "\u{000C}"),
                ],
            ],
            [
                '"\\n"',
                [
                    new Token(TokenType::STRING, new Location(1, 1), "\u{000A}"),
                ],
            ],
            [
                '"\\r"',
                [
                    new Token(TokenType::STRING, new Location(1, 1), "\u{000D}"),
                ],
            ],
            [
                '"\\t"',
                [
                    new Token(TokenType::STRING, new Location(1, 1), "\u{0009}"),
                ],
            ],
            [
                '"\\u1234"',
                [
                    new Token(TokenType::STRING, new Location(1, 1), "\u{1234}"),
                ],
            ],
            [
                '"u1234"',
                [
                    new Token(TokenType::STRING, new Location(1, 1), 'u1234'),
                ],
            ],
            [
                '"abc\\u1234abc"',
                [
                    new Token(TokenType::STRING, new Location(1, 1), "abc\u{1234}abc"),
                ],
            ],
            [
                '"blabla\\t\\"\\nfoobar"',
                [
                    new Token(TokenType::STRING, new Location(1, 1), "blabla\u{0009}\"\u{000A}foobar"),
                ],
            ],
            [
                '""""""',
                [
                    new Token(TokenType::STRING, new Location(1, 1), ''),
                ],
            ],
            [
                '""""""""',
                [
                    new Token(TokenType::STRING, new Location(1, 1), ''),
                    new Token(TokenType::STRING, new Location(1, 7), ''),
                ],
            ],
            [
                '"""' . \PHP_EOL . '"""',
                [
                    new Token(TokenType::STRING, new Location(1, 1), ''),
                ],
            ],
            [
                '"""   """',
                [
                    new Token(TokenType::STRING, new Location(1, 1), ''),
                ],
            ],
            [
                '"""  abc  """',
                [
                    new Token(TokenType::STRING, new Location(1, 1), 'abc  '),
                ],
            ],
            [
                '"""' . \PHP_EOL . \PHP_EOL . \PHP_EOL . '"""',
                [
                    new Token(TokenType::STRING, new Location(1, 1), ''),
                ],
            ],
            [
                '"""' . \PHP_EOL . \PHP_EOL . 'foo' . \PHP_EOL . \PHP_EOL . '"""',
                [
                    new Token(TokenType::STRING, new Location(1, 1), 'foo'),
                ],
            ],
            [
                '"""' . \PHP_EOL . \PHP_EOL . '       foo' . \PHP_EOL . \PHP_EOL . '"""',
                [
                    new Token(TokenType::STRING, new Location(1, 1), 'foo'),
                ],
            ],
            [
                '"""' . \PHP_EOL . ' foo' . \PHP_EOL . '       foo' . \PHP_EOL . '"""',
                [
                    new Token(
                        TokenType::STRING,
                        new Location(1, 1),
                        'foo' . \PHP_EOL . '      foo',
                    ),
                ],
            ],
            [
                '"""   foo' . \PHP_EOL . \PHP_EOL . '  foo' . \PHP_EOL . \PHP_EOL . ' foo"""',
                [
                    new Token(
                        TokenType::STRING,
                        new Location(1, 1),
                        '  foo' . \PHP_EOL . \PHP_EOL . ' foo' . \PHP_EOL . \PHP_EOL . 'foo',
                    ),
                ],
            ],
            [
                '"""\\n"""',
                [
                    new Token(TokenType::STRING, new Location(1, 1), "\\n"),
                ],
            ],
            [
                '"""\\""""""',
                [
                    new Token(TokenType::STRING, new Location(1, 1), '"""'),
                ],
            ],
            [
                '"""\\\\""""""',
                [
                    new Token(TokenType::STRING, new Location(1, 1), '\\"""'),
                ],
            ],
            [
                '"""abc\\"""abc"""',
                [
                    new Token(TokenType::STRING, new Location(1, 1), 'abc"""abc'),
                ],
            ],
            [
                '0',
                [
                    new Token(TokenType::INT, new Location(1, 1), '0'),
                ],
            ],
            [
                '-0',
                [
                    new Token(TokenType::INT, new Location(1, 1), '-0'),
                ],
            ],
            [
                '4',
                [
                    new Token(TokenType::INT, new Location(1, 1), '4'),
                ],
            ],
            [
                '-4',
                [
                    new Token(TokenType::INT, new Location(1, 1), '-4'),
                ],
            ],
            [
                '4.0',
                [
                    new Token(TokenType::FLOAT, new Location(1, 1), '4.0'),
                ],
            ],
            [
                '-4.0',
                [
                    new Token(TokenType::FLOAT, new Location(1, 1), '-4.0'),
                ],
            ],
            [
                '4e10',
                [
                    new Token(TokenType::FLOAT, new Location(1, 1), '4e10'),
                ],
            ],
            [
                '4e0010',
                [
                    new Token(TokenType::FLOAT, new Location(1, 1), '4e0010'),
                ],
            ],
            [
                '-4e10',
                [
                    new Token(TokenType::FLOAT, new Location(1, 1), '-4e10'),
                ],
            ],
            [
                '4E10',
                [
                    new Token(TokenType::FLOAT, new Location(1, 1), '4e10'),
                ],
            ],
            [
                '-4e-10',
                [
                    new Token(TokenType::FLOAT, new Location(1, 1), '-4e-10'),
                ],
            ],
            [
                '4e+10',
                [
                    new Token(TokenType::FLOAT, new Location(1, 1), '4e10'),
                ],
            ],
            [
                '-4e+10',
                [
                    new Token(TokenType::FLOAT, new Location(1, 1), '-4e10'),
                ],
            ],
            [
                'null',
                [
                    new Token(TokenType::NULL, new Location(1, 1)),
                ],
            ],
            [
                'NULL',
                [
                    new Token(TokenType::NAME, new Location(1, 1), 'NULL'),
                ],
            ],
            [
                'Name',
                [
                    new Token(TokenType::NAME, new Location(1, 1), 'Name'),
                ],
            ],
            [
                'NAME',
                [
                    new Token(TokenType::NAME, new Location(1, 1), 'NAME'),
                ],
            ],
            [
                '__Name',
                [
                    new Token(TokenType::NAME, new Location(1, 1), '__Name'),
                ],
            ],
            [
                'Name_with_underscore',
                [
                    new Token(TokenType::NAME, new Location(1, 1), 'Name_with_underscore'),
                ],
            ],
            [
                'FALSE true',
                [
                    new Token(TokenType::NAME, new Location(1, 1), 'FALSE'),
                    new Token(TokenType::TRUE, new Location(1, 7)),
                ],
            ],
            [
                '... type fragment',
                [
                    new Token(TokenType::ELLIP, new Location(1, 1)),
                    new Token(TokenType::TYPE, new Location(1, 5)),
                    new Token(TokenType::FRAGMENT, new Location(1, 10)),
                ],
            ],
            [
                '-4.024E-10',
                [
                    new Token(TokenType::FLOAT, new Location(1, 1), '-4.024e-10'),
                ],
            ],
            [
                'query { field1 { innerField } }',
                [
                    new Token(TokenType::QUERY, new Location(1, 1)),
                    new Token(TokenType::CUR_O, new Location(1, 7)),
                    new Token(TokenType::NAME, new Location(1, 9), 'field1'),
                    new Token(TokenType::CUR_O, new Location(1, 16)),
                    new Token(TokenType::NAME, new Location(1, 18), 'innerField'),
                    new Token(TokenType::CUR_C, new Location(1, 29)),
                    new Token(TokenType::CUR_C, new Location(1, 31)),
                ],
            ],
            [
                'mutation { field(argName: 4) }',
                [
                    new Token(TokenType::MUTATION, new Location(1, 1)),
                    new Token(TokenType::CUR_O, new Location(1, 10)),
                    new Token(TokenType::NAME, new Location(1, 12), 'field'),
                    new Token(TokenType::PAR_O, new Location(1, 17)),
                    new Token(TokenType::NAME, new Location(1, 18), 'argName'),
                    new Token(TokenType::COLON, new Location(1, 25)),
                    new Token(TokenType::INT, new Location(1, 27), '4'),
                    new Token(TokenType::PAR_C, new Location(1, 28)),
                    new Token(TokenType::CUR_C, new Location(1, 30)),
                ],
            ],
            [
                'subscription { field(argName: "str") }',
                [
                    new Token(TokenType::SUBSCRIPTION, new Location(1, 1)),
                    new Token(TokenType::CUR_O, new Location(1, 14)),
                    new Token(TokenType::NAME, new Location(1, 16), 'field'),
                    new Token(TokenType::PAR_O, new Location(1, 21)),
                    new Token(TokenType::NAME, new Location(1, 22), 'argName'),
                    new Token(TokenType::COLON, new Location(1, 29)),
                    new Token(TokenType::STRING, new Location(1, 31), 'str'),
                    new Token(TokenType::PAR_C, new Location(1, 36)),
                    new Token(TokenType::CUR_C, new Location(1, 38)),
                ],
            ],
            [
                'query { field(argName: ["str", "str", $varName]) @directiveName }',
                [
                    new Token(TokenType::QUERY, new Location(1, 1)),
                    new Token(TokenType::CUR_O, new Location(1, 7)),
                    new Token(TokenType::NAME, new Location(1, 9), 'field'),
                    new Token(TokenType::PAR_O, new Location(1, 14)),
                    new Token(TokenType::NAME, new Location(1, 15), 'argName'),
                    new Token(TokenType::COLON, new Location(1, 22)),
                    new Token(TokenType::SQU_O, new Location(1, 24)),
                    new Token(TokenType::STRING, new Location(1, 25), 'str'),
                    new Token(TokenType::COMMA, new Location(1, 30)),
                    new Token(TokenType::STRING, new Location(1, 32), 'str'),
                    new Token(TokenType::COMMA, new Location(1, 37)),
                    new Token(TokenType::VARIABLE, new Location(1, 39), 'varName'),
                    new Token(TokenType::SQU_C, new Location(1, 47)),
                    new Token(TokenType::PAR_C, new Location(1, 48)),
                    new Token(TokenType::DIRECTIVE, new Location(1, 50), 'directiveName'),
                    new Token(TokenType::CUR_C, new Location(1, 65)),
                ],
            ],
            [
                'query {' . \PHP_EOL .
                    'field1 {' . \PHP_EOL .
                        'innerField' . \PHP_EOL .
                    '}' . \PHP_EOL .
                '}',
                [
                    new Token(TokenType::QUERY, new Location(1, 1)),
                    new Token(TokenType::CUR_O, new Location(1, 7)),
                    new Token(TokenType::NEWLINE, new Location(1, 8)),
                    new Token(TokenType::NAME, new Location(2, 1), 'field1'),
                    new Token(TokenType::CUR_O, new Location(2, 8)),
                    new Token(TokenType::NEWLINE, new Location(2, 9)),
                    new Token(TokenType::NAME, new Location(3, 1), 'innerField'),
                    new Token(TokenType::NEWLINE, new Location(3, 11)),
                    new Token(TokenType::CUR_C, new Location(4, 1)),
                    new Token(TokenType::NEWLINE, new Location(4, 2)),
                    new Token(TokenType::CUR_C, new Location(5, 1)),
                ],
            ],
            [
                'query {' . \PHP_EOL .
                    'field1 {' . \PHP_EOL .
                        '# this is comment' . \PHP_EOL .
                        'innerField' . \PHP_EOL .
                    '}' . \PHP_EOL .
                '}',
                [
                    new Token(TokenType::QUERY, new Location(1, 1)),
                    new Token(TokenType::CUR_O, new Location(1, 7)),
                    new Token(TokenType::NEWLINE, new Location(1, 8)),
                    new Token(TokenType::NAME, new Location(2, 1), 'field1'),
                    new Token(TokenType::CUR_O, new Location(2, 8)),
                    new Token(TokenType::NEWLINE, new Location(2, 9)),
                    new Token(TokenType::COMMENT, new Location(3, 1), ' this is comment'),
                    new Token(TokenType::NEWLINE, new Location(3, 18)),
                    new Token(TokenType::NAME, new Location(4, 1), 'innerField'),
                    new Token(TokenType::NEWLINE, new Location(4, 11)),
                    new Token(TokenType::CUR_C, new Location(5, 1)),
                    new Token(TokenType::NEWLINE, new Location(5, 2)),
                    new Token(TokenType::CUR_C, new Location(6, 1)),
                ],
            ],
            [
                'on',
                [
                    new Token(TokenType::ON, new Location(1, 1)),
                ],
            ],
            [
                '&',
                [
                    new Token(TokenType::AMP, new Location(1, 1)),
                ],
            ],
            [
                '|',
                [
                    new Token(TokenType::PIPE, new Location(1, 1)),
                ],
            ],
            [
                '!',
                [
                    new Token(TokenType::EXCL, new Location(1, 1)),
                ],
            ],
            [
                '=',
                [
                    new Token(TokenType::EQUAL, new Location(1, 1)),
                ],
            ],
            [
                'schema SCHEMA type interface union input enum scalar implements repeatable',
                [
                    new Token(TokenType::SCHEMA, new Location(1, 1)),
                    new Token(TokenType::NAME, new Location(1, 8), 'SCHEMA'),
                    new Token(TokenType::TYPE, new Location(1, 15)),
                    new Token(TokenType::INTERFACE, new Location(1, 20)),
                    new Token(TokenType::UNION, new Location(1, 30)),
                    new Token(TokenType::INPUT, new Location(1, 36)),
                    new Token(TokenType::ENUM, new Location(1, 42)),
                    new Token(TokenType::SCALAR, new Location(1, 47)),
                    new Token(TokenType::IMPLEMENTS, new Location(1, 54)),
                    new Token(TokenType::REPEATABLE, new Location(1, 65)),
                ],
            ],
        ];
    }

    /**
     * @dataProvider simpleDataProvider
     * @param string $source
     * @param array $tokens
     */
    public function testSimple(string $source, array $tokens) : void
    {
        $source = new StringSource($source);
        $tokenizer = new Tokenizer($source, false);
        $index = 0;

        foreach ($tokenizer as $token) {
            self::assertSame($tokens[$index]->getType(), $token->getType());
            self::assertSame($tokens[$index]->getValue(), $token->getValue());
            self::assertSame($tokens[$index]->getLocation()->getLine(), $token->getLocation()->getLine());
            self::assertSame($tokens[$index]->getLocation()->getColumn(), $token->getLocation()->getColumn());
            ++$index;
        }
    }

    /**
     * @dataProvider skipDataProvider
     * @param string $source
     * @param array $tokens
     */
    public function testSkip(string $source, array $tokens) : void
    {
        $source = new StringSource($source);
        $tokenizer = new Tokenizer($source);
        $index = 0;

        foreach ($tokenizer as $token) {
            self::assertSame($tokens[$index]->getType(), $token->getType());
            self::assertSame($tokens[$index]->getValue(), $token->getValue());
            self::assertSame($tokens[$index]->getLocation()->getLine(), $token->getLocation()->getLine());
            self::assertSame($tokens[$index]->getLocation()->getColumn(), $token->getLocation()->getColumn());
            ++$index;
        }
    }

    /**
     * @dataProvider noKeywordsDataProvider
     * @param string $source
     * @param array $tokens
     */
    public function testNoKeywords(string $source, array $tokens) : void
    {
        $source = new StringSource($source);
        $tokenizer = new Tokenizer($source, true, false);
        $index = 0;

        foreach ($tokenizer as $token) {
            self::assertSame($tokens[$index]->getType(), $token->getType());
            self::assertSame($tokens[$index]->getValue(), $token->getValue());
            self::assertSame($tokens[$index]->getLocation()->getLine(), $token->getLocation()->getLine());
            self::assertSame($tokens[$index]->getLocation()->getColumn(), $token->getLocation()->getColumn());
            ++$index;
        }
    }

    /**
     * @dataProvider invalidDataProvider
     * @param string $source
     * @param string $exception
     */
    public function testInvalid(string $source, string $exception) : void
    {
        $this->expectException($exception);
        $this->expectExceptionMessage(\constant($exception . '::MESSAGE'));

        $source = new StringSource($source);
        $tokenizer = new Tokenizer($source);

        foreach ($tokenizer as $token) {
            self::assertInstanceOf(Token::class, $token);
        }
    }

    public function testSourceIndex() : void
    {
        $source = new StringSource('query { "ěščřžýá" }');
        $tokenizer = new Tokenizer($source);
        $indexes = [0, 6, 8, 18];
        $index = 0;

        foreach ($tokenizer as $key => $token) {
            self::assertSame($indexes[$index], $key);
            ++$index;
        }

        $index = 0;

        foreach ($tokenizer as $key => $token) {
            self::assertSame($indexes[$index], $key);
            ++$index;
        }
    }

    public function testBlockStringIndent() : void
    {
        $source1 = new StringSource('"""' . \PHP_EOL .
            '    Hello,' . \PHP_EOL .
            '      World!' . \PHP_EOL .
            \PHP_EOL .
            '    Yours,' . \PHP_EOL .
            '      GraphQL.' . \PHP_EOL .
            '"""');
        $source2 = new StringSource('"Hello,\\n  World!\\n\\nYours,\\n  GraphQL."');

        $tokenizer = new Tokenizer($source1);
        $tokenizer->rewind();
        $token1 = $tokenizer->current();
        $tokenizer = new Tokenizer($source2);
        $tokenizer->rewind();
        $token2 = $tokenizer->current();

        self::assertSame($token1->getType(), $token2->getType());
        self::assertSame($token1->getValue(), $token2->getValue());
    }

    public function testRewind() : void
    {
        $source = new StringSource('Hello"World"');

        $tokenizer = new Tokenizer($source, false);
        $tokenizer->rewind();
        self::assertSame('Hello', $tokenizer->current()->getValue());
        $tokenizer->next();
        self::assertSame('World', $tokenizer->current()->getValue());
        $tokenizer->rewind();
        self::assertSame('Hello', $tokenizer->current()->getValue());
    }
}
