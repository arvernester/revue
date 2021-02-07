<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Yugo\Revue\Revue;

class RevueTests extends TestCase
{
    private $mockHandler;
    private $client;

    /**
     * @var Revue
     */
    private $revue;

    protected function setUp(): void
    {
        $this->mockHandler = new MockHandler;
        $this->client = new Client(['handler' => HandlerStack::create($this->mockHandler)]);
        $this->revue = new Revue('token', $this->client);
    }

    protected function tearDown(): void
    {
        $this->mockHandler->reset();
        $this->client = null;
    }

    public function testUnauthorized()
    {
        $this->expectException(ClientException::class);

        $this->mockHandler->append(new Response(401));

        $this->revue->me();
    }

    public function testSubscribe(): void
    {
        $response = [
            'email' => 'user@getrevue.io',
            'first_name' => null,
            'last_name' => null,
        ];
        $this->mockHandler->append(new Response(200, [], json_encode($response)));

        $user = $this->revue->subscribe('user@getrevue.io');

        $this->assertCount(3, $user->asArray());
        $this->assertArrayHasKey('email', $user->asArray());
        $this->assertSame(json_encode($response), $user->asJson());
    }

    public function testSubscribeInvalidEmail(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->revue->subscribe('getrevue.io');
    }

    public function testMe(): void
    {
        $response = ['profile_id' => 'string'];
        $this->mockHandler->append(new Response(200, [], json_encode($response)));

        $revue = $this->revue->me();

        $this->assertSame($response, $revue->asArray());
        $this->assertSame(json_encode($response), $revue->asJson());
    }

    public function testExportAsRawResponse(): void
    {
        $response = [
            [
                1,
                'subscribed.csv',
                100,
                'text/plain',
                'unsubscribed.csv',
                100,
                'text/plain',
            ]
        ];
        $this->mockHandler->append(new Response(200, [], json_encode($response)));

        $exports = $this->revue->exports(false);

        $this->assertSame($response, $exports->asArray());
    }

    public function testExportAsFormattedResponse(): void
    {
        $response = [
            [
                1,
                'subscribed.csv',
                100,
                'text/plain',
                'unsubscribed.csv',
                100,
                'text/plain',
            ]
        ];
        $this->mockHandler->append(new Response(200, [], json_encode($response)));

        $exports = $this->revue->exports(true);


        $expected = [
            'id' => 1,
            'subscribed_file_name' => 'subscribed.csv',
            'subscribed_file_size' => 100,
            'subscribed_content_type' => 'text/plain',
            'unsubscribed_file_name' => 'unsubscribed.csv',
            'unsubscribed_file_size' => 100,
            'unsubscribed_content_type' => 'text/plain',
        ];

        $this->assertSame([$expected], $exports->asArray());
    }

    public function testExportById(): void
    {
        $response = [
            'id' => 1,
            'subscribed_file_name' => 'subscribed.csv',
            'subscribed_file_size' => 100,
            'subscribed_content_type' => 'text/plain',
            'unsubscribed_file_name' => 'unsubscribed.csv',
            'unsubscribed_file_size' => 100,
            'unsubscribed_content_type' => 'text/plain',
            'unsubscribe_url' => 'https://getrevue.io/subscribed.csv',
            'subscribed_url' => 'https://getrevue.io/unsubscribed.csv',
        ];
        $this->mockHandler->append(new Response(200, [], json_encode($response)));

        $export = $this->revue->exportsById(1);

        $this->assertArrayHasKey('id', $export->asArray());
        $this->assertSame($response, $export->asArray());
    }

    public function testInvalidIssuesParam(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->revue->issues('previous');
    }

    public function testAddItemToIssue(): void
    {
        $issueId = 1;
        $now = (new DateTimeImmutable)->format(DateTime::ISO8601);
        $response = [
            'title' => 'Revue',
            'created_at' => $now,
            'url' => $url = 'https://getrevue.io',
            'description' => $description = 'Revue makes it easy for writers and publishers to send editorial newsletters — and get paid',
            'order' => null,
            'title_display' => $title = 'Revue',
            'short_url' => 'getrevue.io',
            'thumb_url' => 'images/thumb/missing.png',
            'default_image' => 'images/web/missing.png',
            'hash_id' => '7PdEoo',
        ];
        $this->mockHandler->append(new Response(200, [], json_encode($response)));

        $addedItem = $this->revue->addItems($issueId, [
            'url' => $url,
            'title' => $title,
            'description' => $description,
            'created_at' => $now,
        ]);

        $this->assertArrayHasKey('hash_id', $addedItem->asArray());
        $this->assertSame($response, $addedItem->asArray());
    }

    public function testGetItems(): void
    {
        $now = (new DateTimeImmutable)->format(DateTime::ISO8601);
        $response = [
            'title' => 'Revue',
            'created_at' => $now,
            'url' => 'https://getrevue.io',
            'description' => 'Revue makes it easy for writers and publishers to send editorial newsletters — and get paid',
            'order' => null,
            'title_display' => 'Revue',
            'short_url' => 'getrevue.io',
            'thumb_url' => 'images/thumb/missing.png',
            'default_image' => 'images/web/missing.png',
            'hash_id' => '7PdEoo',
        ];
        $this->mockHandler->append(new Response(200, [], json_encode([$response])));

        $items = $this->revue->items();

        $this->assertSame([$response], $items->asArray());
    }
}
