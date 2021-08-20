<?php

namespace Tests\Feature\Api\User;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class UpdateUserTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var User $user */
        $user = User::factory()->create();
        $this->user = $user;
    }

    public function testUpdateUser(): void
    {
        $this->assertNotEquals($username = 'new.username', $this->user->username);
        $this->assertNotEquals($email = 'newEmail@example.com', $this->user->email);
        $this->assertNotEquals($bio = 'New bio information.', $this->user->bio);

        // update by one to check required_without_all rule
        $this->actingAs($this->user)
            ->putJson('/api/user', ['user' => ['username' => $username]])
            ->assertOk();
        $this->actingAs($this->user)
            ->putJson('/api/user', ['user' => ['email' => $email]])
            ->assertOk();
        $response = $this->actingAs($this->user)
            ->putJson('/api/user', ['user' => ['bio' => $bio]]);

        $response->assertOk()
            ->assertJson(fn (AssertableJson $json) =>
                $json->has('user', fn (AssertableJson $item) =>
                    $item->whereType('token', 'string')
                        ->whereAll([
                            'username' => $username,
                            'email' => $email,
                            'bio' => $bio,
                            'image' => $this->user->image,
                        ])
                )
            );
    }

    public function testUpdateUserImage(): void
    {
        Storage::fake('public');

        $image = UploadedFile::fake()->image('avatar.jpg');

        $response = $this->actingAs($this->user)
            ->putJson('/api/user', [
                'user' => [
                    'image' => $image,
                ],
            ]);

        Storage::disk('public')
            ->assertExists($imagePath = "images/{$image->hashName()}");

        $response->assertOk()
            ->assertJsonPath('user.image', "/storage/{$imagePath}");
    }

    /**
     * @dataProvider userProvider
     * @param array<mixed> $data
     * @param array<string> $errors
     */
    public function testUpdateUserValidation(array $data, array $errors): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->user)
            ->putJson('/api/user', $data);

        $response->assertStatus(422)
            ->assertInvalid($errors);
    }

    public function testUpdateUserValidationUnique(): void
    {
        /** @var User $anotherUser */
        $anotherUser = User::factory()->create();

        $response = $this->actingAs($this->user)
            ->putJson('/api/user', [
                'user' => [
                    'username' => $anotherUser->username,
                    'email' => $anotherUser->email,
                ],
            ]);

        $response->assertStatus(422)
            ->assertInvalid(['username', 'email']);
    }

    public function testSelfUpdateUserValidationUnique(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/user', [
                'user' => [
                    'username' => $this->user->username,
                    'email' => $this->user->email,
                ],
            ]);

        $response->assertOk();
    }

    public function testUpdateUserSetNull(): void
    {
        Storage::fake('public');

        /** @var User $user */
        $user = User::factory()
            ->withImage()
            ->state(['bio' => 'not-null'])
            ->create();

        $response = $this->actingAs($user)
            ->putJson('/api/user', [
                'user' => [
                    'bio' => null,
                    'image' => null,
                ],
            ]);

        $response->assertOk()
            ->assertJsonPath('user.bio', null)
            ->assertJsonPath('user.image', null);
    }

    public function testUpdateUserWithoutAuth(): void
    {
        $this->putJson('/api/user')
            ->assertUnauthorized();
    }

    /**
     * @return array<int|string, array<mixed>>
     */
    public function userProvider(): array
    {
        $strErrors = ['username', 'email'];
        $allErrors = array_merge($strErrors, ['bio', 'image']);

        return [
            'required' => [[], ['any']],
            'wrong type' => [[
                'user' => [
                    'username' => 123,
                    'email' => null,
                    'bio' => [],
                    'image' => 'string',
                ],
            ], $allErrors],
            'empty strings' => [[
                'user' => [
                    'username' => '',
                    'email' => '',
                ],
            ], $strErrors],
            'bad username' => [['user' => ['username' => 'user n@me']], ['username']],
            'not email' => [['user' => ['email' => 'not an email']], ['email']],
            'file but not image' => [[
                'user' => [
                    'image' => UploadedFile::fake()
                        ->create('file.txt', 100, 'text/plain'),
                ],
            ], ['image']],
        ];
    }
}
