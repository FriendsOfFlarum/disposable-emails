<?php

/*
 * This file is part of fof/disposable-emails.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\DisposableEmails\Tests\integration;

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class DisposableEmailValidationTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-disposable-emails');
    }

    #[Test]
    #[DataProvider('disposableEmailProvider')]
    public function cannot_create_user_with_disposable_email(string $email)
    {
        $response = $this->send(
            $this->request(
                'POST',
                '/api/users',
                [
                    'authenticatedAs' => 1,
                    'json' => [
                        'data' => [
                            'attributes' => [
                                'username' => 'testuser' . rand(1000, 9999),
                                'email' => $email,
                                'password' => 'password123',
                            ],
                        ],
                    ],
                ]
            )
        );

        $this->assertEquals(422, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);

        // Verify error structure
        $this->assertArrayHasKey('errors', $body);
        $this->assertNotEmpty($body['errors']);

        $error = $body['errors'][0];

        // Verify error message mentions disposable email
        $this->assertArrayHasKey('detail', $error);
        $this->assertStringContainsString('disposable', strtolower($error['detail']));

        // Verify error points to the email field
        $this->assertArrayHasKey('source', $error);
        $this->assertArrayHasKey('pointer', $error['source']);
        $this->assertEquals('/data/attributes/email', $error['source']['pointer']);
    }

    #[Test]
    #[DataProvider('legitimateEmailProvider')]
    public function can_create_user_with_legitimate_email(string $email)
    {
        $response = $this->send(
            $this->request(
                'POST',
                '/api/users',
                [
                    'authenticatedAs' => 1,
                    'json' => [
                        'data' => [
                            'attributes' => [
                                'username' => 'user' . substr(md5($email), 0, 20),
                                'email' => $email,
                                'password' => 'password123',
                            ],
                        ],
                    ],
                ]
            )
        );

        $this->assertEquals(201, $response->getStatusCode());
    }

    #[Test]
    public function cannot_update_user_to_disposable_email()
    {
        $this->prepareDatabase([
            'users' => [
                ['id' => 2, 'username' => 'testuser', 'email' => 'user@example.com', 'password' => '$2y$10$test', 'is_email_confirmed' => 1],
            ],
        ]);

        $response = $this->send(
            $this->request(
                'PATCH',
                '/api/users/2',
                [
                    'authenticatedAs' => 1,
                    'json' => [
                        'data' => [
                            'type' => 'users',
                            'id' => '2',
                            'attributes' => [
                                'email' => 'spam@yopmail.com',
                            ],
                        ],
                    ],
                ]
            )
        );

        $this->assertEquals(422, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);

        // Verify error structure
        $this->assertArrayHasKey('errors', $body);
        $this->assertNotEmpty($body['errors']);

        $error = $body['errors'][0];

        // Verify error message mentions disposable email
        $this->assertArrayHasKey('detail', $error);
        $this->assertStringContainsString('disposable', strtolower($error['detail']));

        // Verify error points to the email field
        $this->assertArrayHasKey('source', $error);
        $this->assertArrayHasKey('pointer', $error['source']);
        $this->assertEquals('/data/attributes/email', $error['source']['pointer']);
    }

    #[Test]
    public function can_update_user_to_legitimate_email()
    {
        $this->prepareDatabase([
            'users' => [
                ['id' => 2, 'username' => 'testuser', 'email' => 'old@example.com', 'password' => '$2y$10$test', 'is_email_confirmed' => 1],
            ],
        ]);

        $response = $this->send(
            $this->request(
                'PATCH',
                '/api/users/2',
                [
                    'authenticatedAs' => 1,
                    'json' => [
                        'data' => [
                            'type' => 'users',
                            'id' => '2',
                            'attributes' => [
                                'email' => 'new@gmail.com',
                            ],
                        ],
                    ],
                ]
            )
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    public static function disposableEmailProvider(): array
    {
        // Read a sample of domains from the actual mailchecker list
        $listFile = __DIR__ . '/../../vendor/fgribreau/mailchecker/list.txt';
        $domains = file($listFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        // Take first 10 domains from the list for testing
        $sampleDomains = array_slice($domains, 0, 10);

        return array_map(
            fn($domain) => ["test@{$domain}"],
            $sampleDomains
        );
    }

    public static function legitimateEmailProvider(): array
    {
        return [
            ['test@gmail.com'],
            ['user@outlook.com'],
            ['admin@yahoo.com'],
        ];
    }
}
