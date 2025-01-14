<?php
/*
 * Copyright 2022 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Eloquent;

use CloudCreativity\LaravelJsonApi\Tests\Integration\TestCase;
use DummyApp\Country;
use DummyApp\Post;
use DummyApp\User;

/**
 * Class BelongsToTest
 *
 * Tests a JSON API has-one relationship that relates to an Eloquent belongs-to
 * relationship.
 *
 * In our dummy app, this is the author relationship on the post model.
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class BelongsToTest extends TestCase
{

    /**
     * @var string
     */
    protected $resourceType = 'posts';

    public function testCreateWithNull()
    {
        /** @var Post $post */
        $post = factory(Post::class)->make([
            'author_id' => null,
        ]);

        $data = [
            'type' => 'posts',
            'attributes' => [
                'title' => $post->title,
                'slug' => $post->slug,
                'content' => $post->content,
            ],
            'relationships' => [
                'author' => [
                    'data' => null,
                ],
            ],
        ];

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->includePaths('author')
            ->post($uri =  url('/api/v1/posts'));

        $id = $response
            ->assertCreatedWithServerId($uri, $data)
            ->id();

        $this->assertDatabaseHas('posts', [
            'id' => $id,
            'author_id' => null,
        ]);
    }

    public function testCreateWithRelated()
    {
        /** @var Post $post */
        $post = factory(Post::class)->make();

        $data = [
            'type' => 'posts',
            'attributes' => [
                'title' => $post->title,
                'slug' => $post->slug,
                'content' => $post->content,
            ],
            'relationships' => [
                'author' => [
                    'data' => [
                        'type' => 'users',
                        'id' => (string) $post->author_id,
                    ],
                ],
            ],
        ];

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->includePaths('author')
            ->post($uri = url('/api/v1/posts'));

        $id = $response
            ->assertCreatedWithServerId($uri, $data)
            ->id();

        $this->assertDatabaseHas('posts', [
            'id' => $id,
            'author_id' => $post->author_id,
        ]);
    }

    public function testUpdateReplacesRelationshipWithNull()
    {
        /** @var Post $post */
        $post = factory(Post::class)->create();

        $data = [
            'type' => 'posts',
            'id' => (string) $post->getRouteKey(),
            'attributes' => [
                'title' => $post->title,
                'slug' => $post->slug,
                'content' => $post->content,
            ],
            'relationships' => [
                'author' => [
                    'data' => null,
                ],
            ],
        ];

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->includePaths('author')
            ->patch(url('/api/v1/posts', $post));

        $response->assertFetchedOne($data);

        $this->assertDatabaseHas('posts', [
            'id' => $post->getKey(),
            'author_id' => null,
        ]);
    }

    public function testUpdateReplacesNullRelationshipWithResource()
    {
        /** @var Post $post */
        $post = factory(Post::class)->create([
            'author_id' => null,
        ]);

        /** @var User $user */
        $user = factory(User::class)->create();

        $data = [
            'type' => 'posts',
            'id' => (string) $post->getRouteKey(),
            'attributes' => [
                'title' => $post->title,
                'slug' => $post->slug,
                'content' => $post->content,
            ],
            'relationships' => [
                'author' => [
                    'data' => [
                        'type' => 'users',
                        'id' => (string) $user->getRouteKey(),
                    ],
                ],
            ],
        ];

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->includePaths('author')
            ->patch(url('/api/v1/posts', $post));

        $response->assertFetchedOne($data);

        $this->assertDatabaseHas('posts', [
            'id' => $post->getKey(),
            'author_id' => $user->getKey(),
        ]);
    }

    public function testUpdateChangesRelatedResource()
    {
        /** @var Post $post */
        $post = factory(Post::class)->create();
        $this->assertNotNull($post->author_id);

        /** @var User $user */
        $user = factory(User::class)->create();

        $data = [
            'type' => 'posts',
            'id' => (string) $post->getRouteKey(),
            'attributes' => [
                'title' => $post->title,
                'slug' => $post->slug,
                'content' => $post->content,
            ],
            'relationships' => [
                'author' => [
                    'data' => [
                        'type' => 'users',
                        'id' => (string) $user->getRouteKey(),
                    ],
                ],
            ],
        ];

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->includePaths('author')
            ->patch(url('/api/v1/posts', $post));

        $response->assertFetchedOne($data);

        $this->assertDatabaseHas('posts', [
            'id' => $post->getKey(),
            'author_id' => $user->getKey(),
        ]);
    }

    public function testReadRelated()
    {
        /** @var Post $post */
        $post = factory(Post::class)->create();
        /** @var User $user */
        $user = $post->author;

        $expected = [
            'type' => 'users',
            'id' => (string) $user->getKey(),
            'attributes' => [
                'name' => $user->name,
            ],
        ];

        $response = $this
            ->jsonApi()
            ->get(url('/api/v1/posts', [$post, 'author']));

        $response->assertFetchedOne($expected);
    }

    public function testReadRelatedNull()
    {
        /** @var Post $post */
        $post = factory(Post::class)->create([
            'author_id' => null,
        ]);

        $response = $this
            ->jsonApi()
            ->get(url('/api/v1/posts', [$post, 'author']));

        $response->assertFetchedNull();
    }

    public function testReadRelationship()
    {
        $post = factory(Post::class)->create();

        $response = $this
            ->jsonApi('users')
            ->get(url('/api/v1/posts', [$post, 'relationships', 'author']));

        $response->assertFetchedToOne($post->author);
    }

    public function testReadEmptyRelationship()
    {
        $post = factory(Post::class)->create([
            'author_id' => null,
        ]);

        $response = $this
            ->jsonApi('users')
            ->get(url('/api/v1/posts', [$post, 'relationships', 'author']));

        $response->assertFetchedNull();
    }

    public function testReplaceNullRelationshipWithRelatedResource()
    {
        $post = factory(Post::class)->create([
            'author_id' => null,
        ]);

        $user = factory(User::class)->create();

        $data = ['type' => 'users', 'id' => (string) $user->getRouteKey()];

        $response = $this
            ->withoutExceptionHandling()
            ->jsonApi()
            ->withData($data)
            ->patch(url('/api/v1/posts', [$post, 'relationships', 'author']));

        $response->assertStatus(204);

        $this->assertDatabaseHas('posts', [
            'id' => $post->getKey(),
            'author_id' => $user->getKey(),
        ]);
    }

    public function testReplaceRelationshipWithNull()
    {
        $post = factory(Post::class)->create();
        $this->assertNotNull($post->author_id);

        $response = $this
            ->jsonApi()
            ->withData(null)
            ->patch(url('/api/v1/posts', [$post, 'relationships', 'author']));

        $response->assertStatus(204);

        $this->assertDatabaseHas('posts', [
            'id' => $post->getKey(),
            'author_id' => null,
        ]);
    }

    public function testReplaceRelationshipWithDifferentResource()
    {
        $post = factory(Post::class)->create();
        $this->assertNotNull($post->author_id);

        $user = factory(User::class)->create();

        $data = ['type' => 'users', 'id' => (string) $user->getRouteKey()];

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->patch(url('/api/v1/posts', [$post, 'relationships', 'author']));

        $response->assertStatus(204);

        $this->assertDatabaseHas('posts', [
            'id' => $post->getKey(),
            'author_id' => $user->getKey(),
        ]);
    }

    /**
     * @see https://github.com/cloudcreativity/laravel-json-api/issues/280
     */
    public function testInvalidReplace()
    {
        $post = factory(Post::class)->create();
        $country = factory(Country::class)->create();

        $data = ['type' => 'countries', 'id' => (string) $country->getRouteKey()];

        $expected = [
            'status' => '422',
            'detail' => 'The author field must be a to-one relationship containing users resources.',
            'source' => [
                'pointer' => '/data',
            ],
        ];

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->patch(url('/api/v1/posts', [$post, 'relationships', 'author']));

        $response->assertErrorStatus($expected);
    }
}
