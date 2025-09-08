<?php

namespace Laravel\Wayfinder;

use Illuminate\Database\Eloquent\Model;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\PhpDocParser\ParserConfig;
use ReflectionClass;
use Throwable;

class BindingResolver
{
    protected static $booted = [];

    protected static $columns = [];

    protected static ?PhpDocParser $docParser = null;

    protected static ?Lexer $lexer = null;

    public static function resolveTypeAndKey(string $routable, $key): array
    {
        $booted = self::$booted[$routable] ??= app($routable);

        $key ??= $booted->getRouteKeyName();

        if (! ($booted instanceof Model)) {
            return [null, $key];
        }

        self::$columns[$routable] ??= self::getColumns($booted);

        return [
            collect(self::$columns[$routable])->first(
                fn ($column) => $column['name'] === $key,
            )['type_name'] ?? null,
            $key,
        ];
    }

    protected static function getColumns(Model $model): array
    {
        try {
            return $model->getConnection()->getSchemaBuilder()->getColumns($model->getTable());
        } catch (Throwable) {
            return self::parseDocBlock($model);
        }
    }

    protected static function parseDocBlock(Model $model): array
    {
        $doc = (new ReflectionClass($model))->getDocComment();

        if (! $doc) {
            return [];
        }

        self::$docParser ??= self::initDocParser();
        self::$lexer ??= self::initLexer();

        $tokens = new TokenIterator(self::$lexer->tokenize($doc));
        $phpDocNode = self::$docParser->parse($tokens);

        $tags = array_merge($phpDocNode->getPropertyTagValues(), $phpDocNode->getPropertyReadTagValues(), $phpDocNode->getPropertyWriteTagValues());

        return collect($tags)->map(function ($tag) {
            $type = $tag->type;

            $typeName = match (true) {
                $type instanceof IdentifierTypeNode => $type->name,
                $type instanceof UnionTypeNode => collect($type->types)->filter(fn ($t) => $t instanceof IdentifierTypeNode)->filter(fn ($t) => $t->name !== 'null')->map(fn ($t) => $t->name)->join('|'),
                default => 'mixed',
            };

            return [
                'name' => ltrim($tag->propertyName, '$'),
                'type_name' => $typeName,
            ];
        })->filter()->values()->all();
    }

    protected static function initDocParser(): PhpDocParser
    {
        $config = self::getParserConfig();
        $constExprParser = new ConstExprParser($config);
        $typeParser = new TypeParser($config, $constExprParser);
        $phpDocParser = new PhpDocParser($config, $typeParser, $constExprParser);

        return $phpDocParser;
    }

    protected static function initLexer(): Lexer
    {
        $config = self::getParserConfig();

        return new Lexer($config);
    }

    protected static function getParserConfig(): ParserConfig
    {
        return new ParserConfig(usedAttributes: []);
    }
}
