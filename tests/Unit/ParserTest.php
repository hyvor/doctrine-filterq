<?php

namespace Hyvor\FilterQ\Tests\Unit;

use Hyvor\FilterQ\Exceptions\ParserException;
use Hyvor\FilterQ\Parser;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    public function testParsingKeywords(): void
    {
        $this->assertEquals(
            ['and' => [['key', '=', true]]],
            Parser::parse('key=true')
        );

        $this->assertEquals(
            ['and' => [['key', '!=', false]]],
            Parser::parse('key!=false')
        );

        $this->assertEquals(
            ['and' => [['key', '=', null]]],
            Parser::parse('key=null')
        );
    }

    public function testParsingNumbers(): void
    {
        $this->assertEquals(
            ['and' => [['key', '=', 200]]],
            Parser::parse('key=200')
        );

        $this->assertEquals(
            ['and' => [['key', '>', 2000000000]]],
            Parser::parse('key>2000000000')
        );

        $this->assertEquals(
            ['and' => [['key', '<', -100]]],
            Parser::parse('key<-100')
        );

        $this->assertEquals(
            ['and' => [['key', '<=', 2.5]]],
            Parser::parse('key<=2.5')
        );

        $this->assertEquals(
            ['and' => [['key', '>=', -2.5]]],
            Parser::parse('key>=-2.5')
        );
    }

    public function testParingStrings(): void
    {
        $this->assertEquals(
            ['and' => [['key', '=', 'Hello World']]],
            Parser::parse("key='Hello World'")
        );

        $this->assertEquals(
            ['and' => [['key', '=', "Hello 'World'"]]],
            Parser::parse("key='Hello \'World\''")
        );
    }

    public function testParsingStringsWithoutQuotes(): void
    {
        $this->assertEquals(
            ['and' => [['key', '=', 'hello']]],
            Parser::parse("key=hello")
        );

        $this->assertEquals(
            ['and' => [['key', '=', 'hello_world']]],
            Parser::parse("key=hello_world")
        );

        $this->assertEquals(
            ['and' => [['key', '=', 'hello-world']]],
            Parser::parse("key=hello-world")
        );

        $this->assertEquals(
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
        $this->assertEquals(
            [
                'and' => [
                    ['key1', '=', '1'],
                    [
                        'or' => [
                            ['key2', '=', '2'],
                            ['key3', '=', '3'],
                        ]
                    ]
                ]
            ],
            Parser::parse('key1=1&(key2=2|key3=3)')
        );

        $this->assertEquals(
            [
                'and' => [
                    ['key1', '=', '1'],
                    [
                        'or' => [
                            ['key2', '=', '2'],
                            [
                                'and' => [
                                    ['key3', '=', '3'],
                                    ['key4', '=', '4'],
                                    ['key5', '=', '5'],
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
        $this->assertEquals(
            [
                'and' => [
                    ['key1', '=', '1'],
                    [
                        'or' => [
                            ['key2', '=', '2'],
                            ['key3', '=', '3'],
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
        $this->assertEquals(
            ['and' => [['key', '=', 'hello']]],
            Parser::parse("key='hello'world")
        );
    }

    public function test_parsing_nested(): void
    {
        $this->assertEquals(
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
