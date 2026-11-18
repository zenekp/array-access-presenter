<?php

namespace Zenekp\ArrayAccessPresenter\Tests;

use ArrayObject;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;
use TypeError;
use Zenekp\ArrayAccessPresenter\AbstractPresenter;
use Zenekp\ArrayAccessPresenter\Interfaces\PresenterInterface;

class AbstractPresenterTest extends TestCase
{
    public function test_get_data_returns_the_provided_array(): void
    {
        $data = ['display_name' => 'Ada'];
        $presenter = new TestPresenter($data);

        $this->assertSame($data, $presenter->getData());
    }

    public function test_magic_call_uses_snake_case_for_array_data(): void
    {
        $presenter = new TestPresenter([
            'display_name' => 'Ada',
            'active' => true,
        ]);

        $this->assertSame('Ada', $presenter->displayName());
        $this->assertTrue($presenter->active());
    }

    public function test_magic_call_reads_array_access_data(): void
    {
        $presenter = new TestPresenter(new ArrayObject([
            'display_name' => 'Ada',
        ]));

        $this->assertSame('Ada', $presenter->displayName());
    }

    public function test_has_magic_call_reports_whether_a_value_is_non_empty(): void
    {
        $presenter = new TestPresenter([
            'name' => 'Ada',
            'nickname' => '',
        ]);

        $this->assertTrue($presenter->hasName());
        $this->assertFalse($presenter->hasNickname());
    }

    public function test_missing_magic_call_throws_runtime_exception(): void
    {
        $presenter = new TestPresenter([]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('data key "missing_value"');

        $presenter->missingValue();
    }

    public function test_has_magic_call_returns_false_for_missing_keys(): void
    {
        $presenter = new TestPresenter([]);

        $this->assertFalse($presenter->hasMissing());
        $this->assertFalse($presenter->hasMissingValue());
    }

    public function test_magic_call_preserves_null_zero_false_and_empty_values(): void
    {
        $presenter = new TestPresenter([
            'null_value' => null,
            'zero_value' => 0,
            'false_value' => false,
            'empty_value' => '',
        ]);

        $this->assertNull($presenter->nullValue());
        $this->assertSame(0, $presenter->zeroValue());
        $this->assertFalse($presenter->falseValue());
        $this->assertSame('', $presenter->emptyValue());
    }

    public function test_magic_call_uses_acronym_aware_snake_case(): void
    {
        $presenter = new TestPresenter([
            'http_status' => 204,
            'xml_api_key' => 'key',
        ]);

        $this->assertSame(204, $presenter->HTTPStatus());
        $this->assertSame('key', $presenter->xmlApiKey());
    }

    public function test_missing_array_access_offset_throws_runtime_exception(): void
    {
        $presenter = new TestPresenter(new ArrayObject(['present' => 'value']));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('data key "missing"');

        $presenter->missing();
    }

    public function test_constructor_rejects_unsupported_data_types(): void
    {
        $this->expectException(TypeError::class);

        new TestPresenter(new stdClass);
    }

    public function test_presenter_and_collection_relations(): void
    {
        $presenter = new TestPresenter([
            'author' => ['display_name' => 'Ada'],
            'comments' => [
                ['display_name' => 'Grace'],
                'invalid item',
            ],
        ]);

        $this->assertSame('Ada', $presenter->author()->displayName());
        $this->assertCount(1, $presenter->comments());
        $this->assertSame('Grace', $presenter->comments()[0]->displayName());
    }

    public function test_relation_supports_snake_case_keys_and_array_access_data(): void
    {
        $presenter = new TestPresenter(new ArrayObject([
            'primary_author' => new ArrayObject(['display_name' => 'Ada']),
            'reviewers' => new ArrayObject([
                new ArrayObject(['display_name' => 'Grace']),
                'invalid item',
            ]),
        ]));

        $this->assertSame('Ada', $presenter->primaryAuthor()->displayName());
        $this->assertCount(1, $presenter->reviewers());
        $this->assertSame('Grace', $presenter->reviewers()[0]->displayName());
    }

    public function test_scalar_and_missing_relations_return_typed_defaults(): void
    {
        $presenter = new TestPresenter([
            'author' => 'Ada',
            'comments' => 'not a collection',
            'missing_author' => null,
        ]);

        $this->assertNull($presenter->author());
        $this->assertSame([], $presenter->comments());
    }

    public function test_relation_is_resolved_only_once(): void
    {
        $presenter = new TestPresenter([
            'author' => ['display_name' => 'Ada'],
        ]);

        $first = $presenter->author();
        $second = $presenter->author();

        $this->assertSame($first, $second);
    }

    public function test_collection_ignores_null_scalar_and_nested_collections(): void
    {
        $presenter = new TestPresenter([
            'comments' => [
                null,
                false,
                42,
                [['display_name' => 'nested']],
                ['display_name' => 'Grace'],
            ],
        ]);

        $this->assertCount(1, $presenter->comments());
        $this->assertSame('Grace', $presenter->comments()[0]->displayName());
    }

    public function test_single_relation_rejects_presenter_without_interface(): void
    {
        $presenter = new TestPresenter([
            'invalid_author' => ['display_name' => 'Ada'],
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must be concrete and implement');

        $presenter->invalidAuthor();
    }

    public function test_collection_relation_rejects_presenter_without_interface(): void
    {
        $presenter = new TestPresenter([
            'invalid_comments' => [['display_name' => 'Grace']],
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must be concrete and implement');

        $presenter->invalidComments();
    }

    public function test_relation_rejects_abstract_presenter_class(): void
    {
        $presenter = new TestPresenter([
            'abstract_author' => ['display_name' => 'Ada'],
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must be concrete and implement');

        $presenter->abstractAuthor();
    }
}

class TestPresenter extends AbstractPresenter implements PresenterInterface
{
    protected ?AuthorPresenter $author = null;

    protected ?array $comments = [];

    protected ?AuthorPresenter $primaryAuthor = null;

    protected ?array $reviewers = [];

    protected ?InvalidPresenter $invalidAuthor = null;

    protected ?array $invalidComments = [];

    protected mixed $abstractAuthor = null;

    public function author(): ?AuthorPresenter
    {
        $this->setRelationPresenter(__FUNCTION__, AuthorPresenter::class);

        return $this->author;
    }

    public function comments()
    {
        $this->setCollectionRelationPresenters(__FUNCTION__, CommentPresenter::class);

        return $this->comments;
    }

    public function primaryAuthor(): ?AuthorPresenter
    {
        $this->setRelationPresenter(__FUNCTION__, AuthorPresenter::class);

        return $this->primaryAuthor;
    }

    public function reviewers(): array
    {
        $this->setCollectionRelationPresenters(__FUNCTION__, CommentPresenter::class);

        return $this->reviewers;
    }

    public function invalidAuthor(): ?InvalidPresenter
    {
        $this->setRelationPresenter(__FUNCTION__, InvalidPresenter::class);

        return $this->invalidAuthor;
    }

    public function invalidComments(): array
    {
        $this->setCollectionRelationPresenters(__FUNCTION__, InvalidPresenter::class);

        return $this->invalidComments;
    }

    public function abstractAuthor(): mixed
    {
        $this->setRelationPresenter(__FUNCTION__, AbstractInvalidPresenter::class);

        return $this->abstractAuthor;
    }
}

class AuthorPresenter extends AbstractPresenter implements PresenterInterface {}

class CommentPresenter extends AbstractPresenter implements PresenterInterface {}

class InvalidPresenter extends AbstractPresenter {}

abstract class AbstractInvalidPresenter extends AbstractPresenter implements PresenterInterface {}
