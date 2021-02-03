<?php

namespace Yugo\Revue;

use GuzzleHttp\Client;
use InvalidArgumentException;

class Revue
{

    const VERSION = 'v2';

    const HOST = 'https://www.getrevue.co/api/';

    private $token;

    private $response;

    private $client;

    public function __construct(string $token, ?Client $client = null)
    {
        $this->token = $token;

        $this->client = $client;
        if (is_null($this->client)) {
            $this->client = new Client([
                'base_uri' => self::HOST.self::VERSION.'/',
                'headers' => [
                    'Authorization' => sprintf('Token %s', $this->token),
                ]
            ]);
        }
    }

    public function getVersion(): string
    {
        return self::VERSION;    
    }

    public function lists(?string $id)
    {
        $path = 'lists';
        if (!empty($id)) {
            $path .= '/'.$id;
        }

        return $this->request($path);    
    }

    public function issues(string $stage = null)
    {
        $stages = ['latest', 'current'];
        if (!in_array($stage, $stages)) {
            throw new InvalidArgumentException(sprintf('Path %s is not available.', $stage));
        }

        if (!empty($stage)) {
            $path = 'issues/'.$stage;
        }

        return $this->request($path ?? 'issues');    
    }

    public function subscribers()
    {
        return $this->request($path ?? 'subscribers');    
    }

    public function subscribe(string $email, array $body = [])
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException(sprintf('Email address %s is invalid.', $email));
        }
        $payload['json'] = array_merge(['email' => $email], $body);

        return $this->request('subscribers', 'POST', $payload);    
    }

    public function unsubscribed()
    {
        return $this->request('subscribers/unsubscribed');
    }

    public function me()
    {
        return $this->request('accounts/me');    
    }

    public function request(string $path, string $method = 'GET', array $payload = []): self
    {
        $this->response = $this->client->request($method, $path, $payload);

        return $this;
    }

    public function asJson(): string
    {
        return (string) $this->response->getBody();
    }

    public function asArray(): array
    {
        return json_decode((string) $this->response->getBody(), true); 
    }
}