<?php
declare(strict_types = 1);
namespace RHo\MediaTypeTest;

use PHPUnit\Framework\TestCase;
use RHo\MediaType\MediaType;

final class MediaTypeTest extends TestCase
{

    public function testMediaTypeWithStructuredSyntaxSuffix(): void
    {
        $mt = new MediaType('application', 'prs.api.ela.do+txt+json', [
            'version' => '1',
            'q' => '0.05'
        ]);

        $this->assertSame('application/prs.api.ela.do+txt+json;version=1;q=0.05', (string) $mt);
        $this->assertSame('application', $mt->type());
        $this->assertSame('prs.api.ela.do+txt+json', $mt->subType());
        $this->assertSame('json', $mt->structuredSyntaxSuffix());
        $this->assertSame('1', $mt->parameter('version'));
        $this->assertSame('0.05', $mt->parameter('q'));
        $this->assertNull($mt->parameter('x'));
        $this->assertSame(50, $mt->parameterQ());
    }

    public function testMediaTypeWithoutStructuredSyntaxSuffix(): void
    {
        $mt = new MediaType('application', 'json');

        $this->assertSame('application/json', (string) $mt);
        $this->assertSame('application', $mt->type());
        $this->assertSame('json', $mt->subType());
        $this->assertNull($mt->structuredSyntaxSuffix());
        $this->assertNull($mt->parameter('version'));
        $this->assertNull($mt->parameter('q'));
        $this->assertNull($mt->parameter('x'));
        $this->assertSame(1000, $mt->parameterQ());
    }

    public function testCompareSameMediaTypes(): void
    {
        $mt1 = new MediaType('application', 'json');
        $mt2 = new MediaType('application', 'json');

        $this->assertSame(0, $mt1->compareTo($mt2));
        $this->assertSame(0, $mt2->compareTo($mt1));
    }

    public function testCompareDifferentSubTypeMediaTypes(): void
    {
        $mt1 = new MediaType('application', 'json');
        $mt2 = new MediaType('application', 'xml');

        $this->assertLessThan(0, $mt1->compareTo($mt2));
        $this->assertGreaterThan(0, $mt2->compareTo($mt1));
    }

    public function testCompareDifferentTypeMediaTypes(): void
    {
        $mt1 = new MediaType('image', 'png');
        $mt2 = new MediaType('application', 'xml');

        $this->assertGreaterThan(0, $mt1->compareTo($mt2));
        $this->assertLessThan(0, $mt2->compareTo($mt1));
    }

    public function testCompareMediaTypesWithParameters(): void
    {
        $mt1 = new MediaType('image', 'png', [
            'version' => '1',
            'q' => '0.5'
        ]);
        $mt2 = new MediaType('image', 'png', [
            'q' => '0.7',
            'version' => '1'
        ]);

        $this->assertSame(0, $mt1->compareTo($mt2));
        $this->assertSame(0, $mt2->compareTo($mt1));
    }

    public function testCompareDifferentMediaTypesWithParameters(): void
    {
        $mt1 = new MediaType('image', 'png', [
            'version' => '1'
        ]);
        $mt2 = new MediaType('image', 'png');

        $this->assertSame(1, $mt1->compareTo($mt2));
        $this->assertSame(0, $mt2->compareTo($mt1));
    }
}