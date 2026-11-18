<?php

namespace Zenekp\ArrayAccessPresenter\Tests;

use ArrayObject;
use stdClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use TypeError;
use Zenekp\ArrayAccessPresenter\AbstractPresenter;
use Zenekp\ArrayAccessPresenter\Interfaces\PresenterInterface;

class AbstractPresenterTest extends TestCase
{
    public function testGetDataReturnsTheProvidedArray(): void
    {
        $data = ['display_name' => 'Ada'];
        $presenter = new TestPresenter($data);

        $this->assertSame($data, $presenter->getData());
    }

    public function testMagicCallUsesSnakeCaseForArrayData(): void
    {
        $presenter = new TestPresenter([
            'display_name' => 'Ada',
            'active' => true,
        ]);

        $this->assertSame('Ada', $presenter->displayName());
        $this->assertTrue($presenter->active());
    }

    public function testMagicCallReadsArrayAccessData(): void
    {
        $presenter = new TestPresenter(new ArrayObject([
            'display_name' => 'Ada',
        ]));

        $this->assertSame('Ada', $presenter->displayName());
    }

    public function testHasMagicCallReportsWhetherAValueIsNonEmpty(): void
    {
        $presenter = new TestPresenter([
            'name' => 'Ada',
            'nickname' => '',
        ]);

        $this->assertTrue($presenter->hasName());
        $this->assertFalse($presenter->hasNickname());
    }

    public function testMissingMagicCallThrowsRuntimeException(): void
    {
        $presenter = new TestPresenter([]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('data key "missing_value"');

        $presenter->missingValue();
    }

    public function testHasMagicCallReturnsFalseForMissingKeys(): void
    {
        $presenter = new TestPresenter([]);

        $this->assertFalse($presenter->hasMissing());
        $this->assertFalse($presenter->hasMissingValue());
    }

    public function testMagicCallPreservesNullZeroFalseAndEmptyValues(): void
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

    public function testMagicCallUsesAcronymAwareSnakeCase(): void
    {
        $presenter = new TestPresenter([
            'http_status' => 204,
            'xml_api_key' => 'key',
        ]);

        $this->assertSame(204, $presenter->HTTPStatus());
        $this->assertSame('key', $presenter->xmlApiKey());
    }

    public function testMissingArrayAccessOffsetThrowsRuntimeException(): void
    {
        $presenter = new TestPresenter(new ArrayObject(['present' => 'value']));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('data key "missing"');

        $presenter->missing();
    }

    public function testConstructorRejectsUnsupportedDataTypes(): void
    {
        $this->expectException(TypeError::class);

        new TestPresenter(new stdClass());
    }

    public function testPresenterAndCollectionRelations(): void
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

    public function testRelationSupportsSnakeCaseKeysAndArrayAccessData(): void
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

    public function testScalarAndMissingRelationsReturnTypedDefaults(): void
    {
        $presenter = new TestPresenter([
            'author' => 'Ada',
            'comments' => 'not a collection',
            'missing_author' => null,
        ]);

        $this->assertNull($presenter->author());
        $this->assertSame([], $presenter->comments());
    }

    public function testRelationIsResolvedOnlyOnce(): void
    {
        $presenter = new TestPresenter([
            'author' => ['display_name' => 'Ada'],
        ]);

        $first = $presenter->author();
        $second = $presenter->author();

        $this->assertSame($first, $second);
    }

    public function testCollectionIgnoresNullScalarAndNestedCollections(): void
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
}

class TestPresenter extends AbstractPresenter implements PresenterInterface
{
    protected ?AuthorPresenter $author = null;

    protected ?array $comments = [];

    protected ?AuthorPresenter $primaryAuthor = null;

    protected ?array $reviewers = [];

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
}

class AuthorPresenter extends AbstractPresenter implements PresenterInterface
{

}

class CommentPresenter extends AbstractPresenter implements PresenterInterface
{

}
