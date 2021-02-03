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
}
