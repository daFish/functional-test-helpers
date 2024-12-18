<?php

declare(strict_types=1);

namespace Brainbits\FunctionalTestHelpers\Tests\HttpClientMock;

use Brainbits\FunctionalTestHelpers\HttpClientMock\MockRequestBuilder;
use Brainbits\FunctionalTestHelpers\HttpClientMock\MockRequestBuilderCollection;
use Brainbits\FunctionalTestHelpers\HttpClientMock\MockRequestMatch;
use Brainbits\FunctionalTestHelpers\HttpClientMock\MockRequestMatcher;
use Brainbits\FunctionalTestHelpers\HttpClientMock\MockResponseBuilder;
use Brainbits\FunctionalTestHelpers\HttpClientMock\SymfonyMockResponseFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(MockRequestBuilder::class)]
#[CoversClass(MockRequestBuilderCollection::class)]
#[CoversClass(MockRequestMatch::class)]
#[CoversClass(MockRequestMatcher::class)]
final class MockRequestBuilderCollectionTest extends TestCase
{
    private MockRequestBuilderCollection $collection;
    /** @var MockRequestBuilder[] */
    private array $builders = [];

    public function setUp(): void
    {
        $this->builders = [
            'fallback' => (new MockRequestBuilder())
                ->willRespond(new MockResponseBuilder()),

            'get' => (new MockRequestBuilder())
                ->method('GET')
                ->willRespond(new MockResponseBuilder()),

            'post' => (new MockRequestBuilder())
                ->method('POST')
                ->willRespond(new MockResponseBuilder()),

            'foo' => (new MockRequestBuilder())
                ->uri('/foo')
                ->willRespond(new MockResponseBuilder()),

            'getBar' => (new MockRequestBuilder())
                ->method('GET')
                ->uri('/bar')
                ->willRespond(new MockResponseBuilder()),

            'getBarWithOneParam' => (new MockRequestBuilder())
                ->method('GET')
                ->uri('/bar?one=1')
                ->willRespond(new MockResponseBuilder()),

            'getBarWithTwoParams' => (new MockRequestBuilder())
                ->method('GET')
                ->uri('/bar')
                ->queryParam('one', '1')
                ->queryParam('two', '2')
                ->willRespond(new MockResponseBuilder()),

            'postBarJson' => (new MockRequestBuilder())
                ->method('POST')
                ->uri('/bar')
                ->json(['json' => 'data'])
                ->willRespond(new MockResponseBuilder()),

            'postBarWithOneParam' => (new MockRequestBuilder())
                ->method('POST')
                ->uri('/bar')
                ->requestParam('one', '1')
                ->willRespond(new MockResponseBuilder()),

            'postBarWithTwoParams' => (new MockRequestBuilder())
                ->method('POST')
                ->uri('/bar')
                ->requestParam('one', '1')
                ->requestParam('two', '2')
                ->willRespond(new MockResponseBuilder()),

            'postBarWithContent' => (new MockRequestBuilder())
                ->method('POST')
                ->uri('/bar')
                ->content('content')
                ->willRespond(new MockResponseBuilder()),

            'postBarWithMultipart' => (new MockRequestBuilder())
                ->method('POST')
                ->uri('/barx')
                ->multipart('key', 'application/octet-stream', null, 'content')
                ->willRespond(new MockResponseBuilder()),
        ];

        $this->collection = new MockRequestBuilderCollection(new SymfonyMockResponseFactory());
        foreach ($this->builders as $builder) {
            $this->collection->addMockRequestBuilder($builder);
        }
    }

    /** @param mixed[] $options */
    #[DataProvider('requests')]
    public function testRequestMatching(string $method, string $uri, array $options, string $index): void
    {
        ($this->collection)($method, $uri, $options);

        $expectedMockRequestBuilder = $this->builders[$index];

        self::assertFalse($expectedMockRequestBuilder->getCallStack()->isEmpty());
    }

    /** @return mixed[] */
    public static function requests(): array
    {
        return [
            ['DELETE', '/baz', [], 'fallback'],
            ['GET', '/baz', [], 'get'],
            ['POST', '/baz', [], 'post'],
            ['GET', '/foo', [], 'foo'],
            ['POST', '/foo', [], 'foo'],
            ['DELETE', '/foo', [], 'foo'],
            ['GET', '/bar', [], 'getBar'],
            ['GET', '/bar?one=1', [], 'getBarWithOneParam'],
            ['GET', '/bar?one=1&two=2', [], 'getBarWithTwoParams'],
            ['GET', '/bar', [], 'getBar'],
            ['POST', '/bar', [], 'post'],
            ['POST', '/bar', ['json' => ['json' => 'data']], 'postBarJson'],
            'postBarWithOneParam' => [
                'POST',
                '/bar',
                ['body' => 'one=1', 'headers' => ['Content-Type: application/x-www-form-urlencoded']],
                'postBarWithOneParam',
            ],
            'postBarWithTwoParams' => [
                'POST',
                '/bar',
                ['body' => 'one=1&two=2', 'headers' => ['Content-Type: application/x-www-form-urlencoded']],
                'postBarWithTwoParams',
            ],
            'postBarWithContent' => [
                'POST',
                '/bar',
                ['body' => 'content', 'headers' => ['Content-Type: application/x-www-form-urlencoded']],
                'postBarWithContent',
            ],
            'postBarWithMultipart' => [
                'POST',
                '/barx',
                [
                    'body' => <<<'BODY'
                    --12345
                    Content-Disposition: form-data; name="key"
                    
                    content
                    --12345--
                    BODY,
                    'headers' => ['Content-Type: multipart/form-data; boundary=12345'],
                ],
                'postBarWithMultipart',
            ],
        ];
    }
}
