<?php

namespace Yugo\Revue;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
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
                'base_uri' => self::HOST . self::VERSION . '/',
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

    public function listsById($id)
    {
        return $this->request('lists/' . $id);
    }

    public function lists()
    {
        return $this->request('lists');
    }

    public function exportsById($id)
    {
        return $this->request('exports/' . $id);
    }

    public function exportsList($id)
    {
        return $this->request('exports/lists/' . $id, 'POST');
    }

    public function exports(bool $reformat = true)
    {
        if ($reformat === true) {
            $request = $this->request('exports')->asArray();

            $keys = [
                'id',
                'subscribed_file_name',
                'subscribed_file_size',
                'subscribed_content_type',
                'unsubscribed_file_name',
                'unsubscribed_file_size',
                'unsubscribed_content_type',
            ];

            $formatted = array_map(function ($export) use ($keys) {
                return array_combine($keys, $export);
            }, $request);

            $this->response = new Response(200, [], json_encode($formatted));

            return $this;
        }

        return $this->request($path ?? 'exports');
    }

    public function addItems($issuesId, array $data = [])
    {
        $payload['form_params'] = $data;

        return $this->request('issues/' . $issuesId . '/items', 'POST', $payload);
    }

    public function items()
    {
        return $this->request('items');
    }

    public function issues(string $stage = null)
    {
        $stages = ['latest', 'current'];
        if (!empty($stage) && !in_array($stage, $stages)) {
            throw new InvalidArgumentException(sprintf('Path %s is not available.', $stage));
        }

        if (!empty($stage)) {
            $path = 'issues/' . $stage;
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
