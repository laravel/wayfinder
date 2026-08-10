<?php

namespace App\Enums;

use App\Enums\Concerns\HasIcon;
use Illuminate\Support\Collection;
use Illuminate\Support\Stringable;
use RuntimeException;
use stdClass;

enum OrderStatus: string
{
    use HasIcon;

    case Pending = 'pending';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Awaiting Payment',
            self::Shipped => 'On Its Way',
            self::Delivered => 'Delivered',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Delivered => 'green',
            self::Cancelled => 'red',
            default => 'gray',
        };
    }

    public function description(): ?string
    {
        return match ($this) {
            self::Cancelled => 'The customer said "no thanks" — see /docs/refunds.',
            default => null,
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Delivered, self::Cancelled], true);
    }

    public function weight(): int
    {
        return match ($this) {
            self::Pending => 1,
            self::Shipped => 2,
            self::Delivered => 3,
            self::Cancelled => 4,
        };
    }

    public function tags(): array
    {
        return ['order', $this->value];
    }

    public function badge(): array
    {
        return ['tone' => $this->color(), 'text' => $this->label()];
    }

    public function channel(): NotificationChannel
    {
        return $this->isFinal() ? NotificationChannel::Mail : NotificationChannel::Database;
    }

    public function steps(): Collection
    {
        return collect(['placed', $this->value]);
    }

    public function heading(): Stringable
    {
        return str($this->label())->upper();
    }

    public function trackingUrl(): string
    {
        return match ($this) {
            self::Shipped => 'https://example.com/track/shipped',
            self::Delivered => 'https://example.com/track/delivered',
            self::Cancelled => 'https://example.com/track/cancelled',
        };
    }

    public function unavailable(): string
    {
        throw new RuntimeException('Not resolvable at generation time.');
    }

    public function payload(): stdClass
    {
        return new stdClass;
    }

    public function slug(string $prefix = 'order'): string
    {
        return $prefix.'-'.$this->value;
    }

    public function is(OrderStatus $other): bool
    {
        return $this === $other;
    }

    public function touch(): void
    {
        //
    }

    public static function fallback(): self
    {
        return self::Pending;
    }

    protected function internalName(): string
    {
        return 'internal-'.$this->value;
    }

    private function secretName(): string
    {
        return 'secret-'.$this->value;
    }
}
