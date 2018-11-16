<?php
declare(strict_types = 1);
namespace RHo\MediaTypeTest;

use PHPUnit\Framework\TestCase;
use RHo\MediaType\ {
    MediaTypeFactory,
    MediaTypeInterface
};

final class MediaTypeFactoryTest extends TestCase
{

    public function testIsLinearWhiteSpace()
    {
        $this->assertSame(1, preg_match('/^' . MediaTypeFactory::linear_white_space . '$/', "\r\n "));
        $this->assertSame(1, preg_match('/^' . MediaTypeFactory::linear_white_space . '$/', "\r\n "));
        $this->assertSame(1, preg_match('/^' . MediaTypeFactory::linear_white_space . '$/', " \t"));
        $this->assertSame(1, preg_match('/^' . MediaTypeFactory::linear_white_space . '$/', "\t\t"));
        $this->assertSame(1, preg_match('/^' . MediaTypeFactory::linear_white_space . '$/', "\t \t "));
        $this->assertSame(0, preg_match('/^' . MediaTypeFactory::linear_white_space . '$/', ""));
    }

    public function testIsSpecials()
    {
        $this->assertSame(1, preg_match('/^[' . MediaTypeFactory::specials . ']+$/', '(]<>@,;:\\.[)"'));
        $this->assertSame(0, preg_match('/^[' . MediaTypeFactory::specials . ']+$/', '(?]<=>@,/;:\\[)"'));
        $this->assertSame(1, preg_match('/^[' . MediaTypeFactory::tspecials . ']+$/', '(?]<=>@,/;:\\[)"'));
        $this->assertSame(0, preg_match('/^[' . MediaTypeFactory::tspecials . ']+$/', '(]<>@,;:\\.[)"'));
    }

    public function testIsToken()
    {
        $this->assertSame(1, preg_match('/^' . MediaTypeFactory::token . '$/', '!#$%&\'*+-.0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ^_`abcdefghijklmnopqrstuvwxyz{|}~'));
    }

    public function testIsQuotedString()
    {
        $this->assertSame(0, preg_match('/^' . MediaTypeFactory::quoted_string . '$/', 'apple'));
        $this->assertSame(1, preg_match('/^' . MediaTypeFactory::quoted_string . '$/', '"apple"'));
        $this->assertSame(1, preg_match('/^' . MediaTypeFactory::quoted_string . '$/', '"\a\p\p\l\e"'));
        $this->assertSame(1, preg_match('/^' . MediaTypeFactory::quoted_string . '$/', '""'));
        $this->assertSame(1, preg_match('/^' . MediaTypeFactory::quoted_string . '$/', "\"ap\r\n p\tle\""));
        $this->assertSame(1, preg_match('/^' . MediaTypeFactory::quoted_string . '$/', "\"\ap\r\n p\tle" . '\c"'));
    }

    public function testIsContentType()
    {
        $matches = NULL;
        $this->assertSame(1, preg_match('/^' . MediaTypeFactory::content . '$/', 'plain/text'));
        $this->assertSame(1, preg_match('/^' . MediaTypeFactory::content . '$/', 'application/prs.api.ela.do+xml+json', $matches));
        $this->assertSame('application', $matches[1]);
        $this->assertSame('prs.api.ela.do+xml+json', $matches[2]);
        $this->assertSame(1, preg_match('/^' . MediaTypeFactory::content . '$/', 'application/prs.api.ela.do+json;version=1;q=2', $matches));
        $this->assertSame('application', $matches[1]);
        $this->assertSame('prs.api.ela.do+json', $matches[2]);
        $this->assertSame(';version=1;q=2', $matches[3]);
    }

    public function validMediaTypeProvider()
    {
        return [
            [
                'application/prs.api.ela.do+json;version=1;q=0.001',
                'application',
                'prs.api.ela.do+json',
                'json',
                '1',
                1
            ],
            [
                'plain/text;version=2;q=0.5',
                'plain',
                'text',
                NULL,
                '2',
                500
            ],
            [
                'image/jpeg',
                'image',
                'jpeg',
                NULL,
                NULL,
                1000
            ]
        ];
    }

    /**
     *
     * @dataProvider validMediaTypeProvider
     */
    public function testValidMediaType(string $str, string $type, string $subType, ?string $suffix, ?string $version, int $q): void
    {
        $mtf = new MediaTypeFactory();
        $mt = $mtf->fromString($str)->build();
        $this->assertSame(1, count($mt));
        $this->assertInstanceOf(MediaTypeInterface::class, $mt[0]);
        $this->assertSame($type, $mt[0]->type());
        $this->assertSame($subType, $mt[0]->subType());
        $this->assertSame($suffix, $mt[0]->structuredSyntaxSuffix());
        $this->assertSame($version, $mt[0]->parameter('version'));
        $this->assertSame($q, $mt[0]->parameterQ());
        $this->assertNull($mt[0]->parameter('not_defined'));
        $this->assertSame($str, (string) $mt[0]);
    }

    public function testCompareMediaTypes(): void
    {
        $mtf = new MediaTypeFactory();
        $mt1 = $mtf->fromString('plain/text')->build()[0];
        $mt2 = $mtf->fromString('plain/text;v=1')->build()[0];
        $mt3 = $mtf->fromString('application/json')->build()[0];
        $mt4 = $mtf->fromString('plain/html')->build()[0];

        $this->assertSame(0, $mt1->compareTo($mt2));
        $this->assertSame(1, $mt2->compareTo($mt1));
        $this->assertSame(1, $mt2->compareTo($mt3));
        $this->assertSame(1, $mt1->compareTo($mt4));
    }

    public function invalidMediaTypeProvider()
    {
        return [
            [
                '*/*'
            ],
            [
                'plain/*'
            ],
            [
                'application/json;q=1.001'
            ],
            [
                'application/json,plain/text,*/*'
            ]
        ];
    }

    /**
     *
     * @dataProvider invalidMediaTypeProvider
     */
    public function testInvalidMediaType(string $str): void
    {
        $mtf = new MediaTypeFactory();
        $mt = $mtf->fromString($str)->build();
        $this->assertNull($mt);
    }

    /**
     *
     * @expectedException RuntimeException
     * @expectedExceptionMessage preg_match: regular expression failed
     * @expectedExceptionCode PREG_BACKTRACK_LIMIT_ERROR
     */
    public function testRegexpError(): void
    {
        $GLOBALS['mock_preg_match'] = TRUE;
        $GLOBALS['preg_last_error'] = PREG_BACKTRACK_LIMIT_ERROR;
        (new MediaTypeFactory())->fromString('audio/basic')->build();
    }

    public function validMultiMediaTypeProvider()
    {
        return [
            [
                'application/prs.api.ela.do+json;version=1;q=0.4,application/prs.err.ela.do+xml;version=2;q=0.6',
                [
                    'application/prs.api.ela.do+json;version=1;q=0.4',
                    'application/prs.err.ela.do+xml;version=2;q=0.6'
                ],
                [
                    'application',
                    'application'
                ],
                [
                    'prs.api.ela.do+json',
                    'prs.err.ela.do+xml'
                ],
                [
                    'json',
                    'xml'
                ],
                [
                    '1',
                    '2'
                ],
                [
                    400,
                    600
                ]
            ]
        ];
    }

    /**
     *
     * @dataProvider validMultiMediaTypeProvider
     */
    public function testValidMultiMediaType(string $str, array $mts, array $types, array $subTypes, array $suffixes, array $versions, array $qValues): void
    {
        $mtf = new MediaTypeFactory();
        $mt = $mtf->fromString($str)->build();
        $this->assertSame(2, count($mt));
        for ($i = 0; $i < count($mt); ++ $i) {
            $this->assertInstanceOf(MediaTypeInterface::class, $mt[$i]);
            $this->assertSame($types[$i], $mt[$i]->type());
            $this->assertSame($subTypes[$i], $mt[$i]->subType());
            $this->assertSame($suffixes[$i], $mt[$i]->structuredSyntaxSuffix());
            $this->assertSame($versions[$i], $mt[$i]->parameter('version'));
            $this->assertSame($qValues[$i], $mt[$i]->parameterQ());
            $this->assertNull($mt[$i]->parameter('not_defined'));
            $this->assertSame($mts[$i], (string) $mt[$i]);
        }
    }
}