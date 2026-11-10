<?php
// tests/Unit/HelpersTest.php
use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase {
    public function testEFunctionEscapesHtml(): void {
        $this->assertEquals('&lt;script&gt;alert(1)&lt;/script&gt;', e('<script>alert(1)</script>'));
        $this->assertEquals('Hello &amp; World', e('Hello & World'));
        $this->assertEquals('It&#039;s fine', e("It's fine"));
    }

    public function testEFunctionHandlesEmptyString(): void {
        $this->assertEquals('', e(''));
    }

    public function testPaginationFirstPage(): void {
        $result = pagination(1, 25, 10);
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(3, $result['pages']);
        $this->assertEquals(0, $result['offset']);
        $this->assertEquals(10, $result['limit']);
        $this->assertEquals(25, $result['total']);
    }

    public function testPaginationSecondPage(): void {
        $result = pagination(2, 25, 10);
        $this->assertEquals(2, $result['page']);
        $this->assertEquals(3, $result['pages']);
        $this->assertEquals(10, $result['offset']);
    }

    public function testPaginationLastPage(): void {
        $result = pagination(3, 25, 10);
        $this->assertEquals(3, $result['page']);
        $this->assertEquals(3, $result['pages']);
        $this->assertEquals(20, $result['offset']);
    }

    public function testPaginationClampsAboveMax(): void {
        $result = pagination(99, 25, 10);
        $this->assertEquals(3, $result['page']);
    }

    public function testPaginationClampsBelowMin(): void {
        $result = pagination(0, 25, 10);
        $this->assertEquals(1, $result['page']);
    }

    public function testPaginationSinglePage(): void {
        $result = pagination(1, 5, 10);
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(1, $result['pages']);
        $this->assertEquals(0, $result['offset']);
    }

    public function testPaginationEmptyResult(): void {
        $result = pagination(1, 0, 10);
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(1, $result['pages']);
        $this->assertEquals(0, $result['offset']);
    }

    public function testRenderPagerSinglePageReturnsEmpty(): void {
        $html = renderPager('/test.php?', 1, 1);
        $this->assertEquals('', $html);
    }

    public function testRenderPagerMultiplePages(): void {
        $html = renderPager('/test.php?', 1, 3);
        $this->assertStringContainsString('Next', $html);
        $this->assertStringContainsString('page=2', $html);
        $this->assertStringNotContainsString('Previous', $html);
    }

    public function testRenderPagerMiddlePage(): void {
        $html = renderPager('/test.php?', 2, 3);
        $this->assertStringContainsString('Previous', $html);
        $this->assertStringContainsString('Next', $html);
        $this->assertStringContainsString('page=1', $html);
        $this->assertStringContainsString('page=3', $html);
    }

    public function testRenderPagerLastPage(): void {
        $html = renderPager('/test.php?', 3, 3);
        $this->assertStringContainsString('Previous', $html);
        $this->assertStringNotContainsString('Next', $html);
    }

    public function testBadgeFunction(): void {
        $html = badge('Harvested', ['Harvested' => 'bg-green-100 text-green-700']);
        $this->assertStringContainsString('Harvested', $html);
        $this->assertStringContainsString('bg-green-100', $html);
    }

    public function testBadgeDefaultClass(): void {
        $html = badge('Unknown');
        $this->assertStringContainsString('bg-gray-100', $html);
    }
}
