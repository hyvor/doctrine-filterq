<?php

namespace Hyvor\FilterQ\Tests\Unit;

use Hyvor\FilterQ\Exceptions\ParserException;
use Hyvor\FilterQ\Parser;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    public function testParsingKeywords(): void
    {
        $this->assertSame(
            ['and' => [['key', '=', true]]],
            Parser::parse('key=true')
        );

        $this->assertSame(
            ['and' => [['key', '!=', false]]],
            Parser::parse('key!=false')
        );

        $this->assertSame(
            ['and' => [['key', '=', null]]],
            Parser::parse('key=null')
        );
    }

    public function testParsingNumbers(): void
    {
        $this->assertSame(
            ['and' => [['key', '=', 200]]],
            Parser::parse('key=200')
        );

        $this->assertSame(
            ['and' => [['key', '>', 2000000000]]],
            Parser::parse('key>2000000000')
        );

        $this->assertSame(
            ['and' => [['key', '<', -100]]],
            Parser::parse('key<-100')
        );

        $this->assertSame(
            ['and' => [['key', '<=', 2.5]]],
            Parser::parse('key<=2.5')
        );

        $this->assertSame(
            ['and' => [['key', '>=', -2.5]]],
            Parser::parse('key>=-2.5')
        );
    }

    public function testParingStrings(): void
    {
        $this->assertSame(
            ['and' => [['key', '=', 'Hello World']]],
            Parser::parse("key='Hello World'")
        );

        $this->assertSame(
            ['and' => [['key', '=', "Hello 'World'"]]],
            Parser::parse("key='Hello \'World\''")
        );
    }

    public function testParsingStringsWithoutQuotes(): void
    {
        $this->assertSame(
            ['and' => [['key', '=', 'hello']]],
            Parser::parse("key=hello")
        );

        $this->assertSame(
            ['and' => [['key', '=', 'hello_world']]],
            Parser::parse("key=hello_world")
        );

        $this->assertSame(
            ['and' => [['key', '=', 'hello-world']]],
            Parser::parse("key=hello-world")
        );

        $this->assertSame(
            ['and' => [['key', '=', '_0139210a-fejlwq']]],
            Parser::parse("key=_0139210a-fejlwq")
        );
    }

    public function testExceptionNoClosingParentheses(): void
    {
        $this->expectException(ParserException::class);
        Parser::parse('some=2&(dawl=21');
    }

    public function testExceptionNoClosingQuote(): void
    {
        $this->expectException(ParserException::class);
        Parser::parse("key='hello");
    }

    public function testExceptionAndOrTogether(): void
    {
        $this->expectException(ParserException::class);
        Parser::parse('key=1|key2=2&key3=3');
    }

    public function testParsingNested(): void
    {
        $this->assertSame(
            [
                'and' => [
                    ['key1', '=', 1],
                    [
                        'or' => [
                            ['key2', '=', 2],
                            ['key3', '=', 3],
                        ]
                    ]
                ]
            ],
            Parser::parse('key1=1&(key2=2|key3=3)')
        );

        $this->assertSame(
            [
                'and' => [
                    ['key1', '=', 1],
                    [
                        'or' => [
                            ['key2', '=', 2],
                            [
                                'and' => [
                                    ['key3', '=', 3],
                                    ['key4', '=', 4],
                                    ['key5', '=', 5],
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            Parser::parse('key1=1&(key2=2|(key3=3&key4=4&key5=5))')
        );
    }

    public function testParsingMultiline(): void
    {
        $this->assertSame(
            [
                'and' => [
                    ['key1', '=', 1],
                    [
                        'or' => [
                            ['key2', '=', 2],
                            ['key3', '=', 3],
                        ]
                    ]
                ]
            ],
            Parser::parse("
                key1 = 1 &
                (
                    key2 = 2 |
                    key3 = 3
                )
            ")
        );
    }

    public function testParsingInvalid(): void
    {
        $this->assertSame(
            ['and' => [['key', '=', 'hello']]],
            Parser::parse("key='hello'world")
        );
    }

    public function test_parsing_nested(): void
    {
        $this->assertSame(
            [
                'or' => [
                    [
                        'and' => [
                            ['key1', '=', 1],
                            ['key', '=', 2]
                        ]
                    ],
                    [
                        'and' => [
                            ['key', '=', 2]
                        ]
                    ]
                ]
            ],
            Parser::parse('((key1=1&key=2)|(key=2))')
        );
    }
}
