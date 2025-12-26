<?php

namespace Laravel\Wayfinder\Converters;

use Illuminate\Support\Collection;
use Laravel\Ranger\Components\BroadcastChannel;
use Laravel\Wayfinder\Langs\TypeScript;
use Laravel\Wayfinder\Results\Result;
use Laravel\Wayfinder\Support\Path;

class BroadcastChannels extends Converter
{
    protected const PARAM_PATTERN = '/\{([^\}]+)\}/';

    /**
     * @param  Collection<BroadcastChannel>  $channels
     */
    public function convert(Collection $channels): ?Result
    {
        if ($channels->isEmpty()) {
            return null;
        }

        $types = $channels->map(
            fn (BroadcastChannel $channel) => TypeScript::backtick(
                preg_replace(
                    self::PARAM_PATTERN,
                    TypeScript::templateString(TypeScript::union(['string', 'number'])),
                    $channel->name,
                ),
            ),
        );

        $js = $channels
            ->map($this->toConstData(...))
            ->values()
            ->flatMap(fn ($i) => $i)
            ->undot()
            ->map($this->channelChain(...));

        $content = [
            (string) TypeScript::literalUnion('BroadcastChannel', $types)->export(),
            (string) TypeScript::constant(
                'BroadcastChannels',
                TypeScript::objectWithOnlyKeys($js),
            )->export(),
        ];

        return new Result('broadcast-channels.ts', implode(PHP_EOL.PHP_EOL, $content));
    }

    protected function toConstData(BroadcastChannel $channel): array
    {
        $returnType = TypeScript::backtick(
            preg_replace(
                self::PARAM_PATTERN,
                TypeScript::templateString(TypeScript::union(['string', 'number'])),
                $channel->name,
            ),
        );

        $parts = collect(explode('.', $channel->name));

        $funcParams = [];
        $chain = [];

        foreach ($parts->reverse() as $part) {
            if (preg_match('/\{([^\}]+)\}/', $part, $matches)) {
                $funcParams[] = $matches[1];
            } else {
                $chain[$part] = [
                    '__meta' => [
                        'params' => array_reverse($funcParams),
                        'returnType' => $returnType,
                        'name' => $channel->name,
                    ],
                ];
                $funcParams = [];
            }
        }

        $key = '';
        $nested = [];

        foreach ($parts as $part) {
            if (($chain[$part] ?? false) !== false) {
                $key .= '.'.$part;
                $key = ltrim($key, '.');
                $nested[$key] = $chain[$part];
            }
        }

        return $nested;
    }

    protected function channelChain($item, $key): string
    {
        $meta = $item['__meta'] ?? [];
        $isFinalSegment = array_keys($item) === ['__meta'];

        if ($isFinalSegment) {
            $value = TypeScript::block($this->convertTemplateString($meta['name']))->backtick()->asConst();

            $channelsRelativePath = 'routes/channels.php';
            $channelsPath = Path::firstFromBasePath('routes/channels.php');

            if (count($meta['params']) === 0) {
                $object = TypeScript::objectKeyValue($key, $value);

                if ($channelsPath) {
                    $object->link($channelsRelativePath, $channelsPath);
                }

                return (string) $object;
            }

            $object = TypeScript::objectKeyValue(
                $key,
                sprintf('(%s) => %s', $this->toTypeScriptParams($meta['params']), $value),
            );

            if ($channelsPath) {
                $object->link($channelsRelativePath, $channelsPath);
            }

            return (string) $object;
        }

        if (count($meta['params']) === 0) {
            return TypeScript::objectKeyValue($key, $this->nestedObject($item));
        }

        return TypeScript::objectKeyValue(
            $key,
            sprintf(
                '(%s) => (%s)',
                $this->toTypeScriptParams($meta['params']),
                $this->nestedObject($item),
            ),
        );
    }

    protected function nestedObject(array $item): string
    {
        return TypeScript::objectWithOnlyKeys(
            collect($item)->except(['__meta'])->map($this->channelChain(...)),
        );
    }

    protected function toTypeScriptParams(array $params): string
    {
        return collect($params)->map(fn ($p) => "{$p}: string | number")->implode(', ');
    }

    protected function convertTemplateString(string $templateString): string
    {
        return str_replace('{', '${', $templateString);
    }
}
