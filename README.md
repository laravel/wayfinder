<p align="center"><img src="./art/logo.svg" alt="Laravel Wayfinder"></p>

<p align="center">
<a href="https://github.com/laravel/wayfinder/actions"><img src="https://github.com/laravel/wayfinder/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/wayfinder"><img src="https://img.shields.io/packagist/dt/laravel/wayfinder" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/wayfinder"><img src="https://img.shields.io/packagist/v/laravel/wayfinder" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/wayfinder"><img src="https://img.shields.io/packagist/l/laravel/wayfinder" alt="License"></a>
</p>

## Introduction

Laravel Wayfinder bridges your Laravel backend and TypeScript frontend with zero friction. It automatically generates fully-typed TypeScript from your Laravel code. Stop manually maintaining route URLs, request types, and API contracts — Wayfinder keeps your frontend and backend perfectly in sync.

> [!IMPORTANT]
> Wayfinder is currently in Beta, the API is subject (and likely) to change prior to the v1.0.0 release. All notable changes will be documented in the [changelog](./CHANGELOG.md).

Wayfinder generates TypeScript for:

-   **Routes & Controller Actions** - Type-safe route functions with parameter validation
-   **Named Routes** - Access routes by their Laravel route names
-   **Form Requests** - TypeScript types derived from your validation rules
-   **Eloquent Models** - TypeScript interfaces matching your model attributes and relationships
-   **PHP Enums** - TypeScript types and constants for your enum cases
-   **Inertia.js Page Props** - Types for your Inertia page data
-   **Inertia Shared Data** - Types for data shared across all Inertia pages
-   **Broadcast Channels** - Type-safe channel name builders
-   **Broadcast Events** - Types for your WebSocket event payloads
-   **Environment Variables** - Typed `import.meta.env` for your Vite variables

## Installation

You may install Wayfinder via Composer:

```bash
composer require laravel/wayfinder:dev-next
```

After installation, you may publish the configuration file:

```bash
php artisan vendor:publish --tag=wayfinder-config
```

## Upgrading from Previous Beta

This version of Wayfinder generates _way_ more TypeScript than the previous beta. If you are upgrading from the previous beta version, please note the following changes:

-   All files are generated, by default, under `resources/js/wayfinder`. Previously they were broken up between `actions` and `routes`.
-   `types.ts` is now `types.d.ts`
-   There is still a `routes` subdirectory for named routes, but there is no more `actions` subdirectory, the generated files simply follow the PHP namespace.
-   The following command line flags have been removed and replaced with corresponding config values in the `wayfinder` config:
    -   `--skip-actions`
    -   `--skip-routes`
    -   `--with-form`
-   If you are using the Wayfinder Vite plugin, remove the `routes`, `actions`, and `withForm` arguments

## Generating TypeScript

To generate TypeScript files from your Laravel application, run the `wayfinder:generate` command:

```bash
php artisan wayfinder:generate
```

By default, Wayfinder outputs files to `resources/js/wayfinder`. You can customize this with the `--path` option:

```bash
php artisan wayfinder:generate --path=resources/ts/api
```

### Command Options

| Option        | Description                          |
| ------------- | ------------------------------------ |
| `--path`      | Output directory for generated files |
| `--base-path` | Comma-separated base paths to scan   |
| `--app-path`  | Comma-separated app paths to scan    |
| `--fresh`     | Clear the cache before generating    |

### Automating Generation

For development, you may want to regenerate TypeScript whenever your PHP files change. Add the command to your build process or use a file watcher.

## Routes & Controller Actions

Wayfinder generates TypeScript functions for every route in your application. These functions provide type-safe URL generation with full IDE autocomplete.

### Basic Usage

Given a Laravel controller:

```php
class PostController
{
    public function index() { /* ... */ }
    public function show(Post $post) { /* ... */ }
    public function store(StorePostRequest $request) { /* ... */ }
}
```

Wayfinder generates callable route functions:

```typescript
import { PostController } from "@/wayfinder/App/Http/Controllers/PostController";

// Get URL and method for a route
PostController.index();
// { url: '/posts', method: 'get' }

// Routes with parameters
PostController.show({ post: 1 });
// { url: '/posts/1', method: 'get' }
```

### URL Generation

Each route function includes a `.url()` method for generating just the URL string:

```typescript
PostController.index.url();
// '/posts'

PostController.show.url({ post: 42 });
// '/posts/42'

PostController.edit.url({ post: 42 });
// '/posts/42/edit'
```

### Query Parameters

Add query parameters to any route:

```typescript
PostController.index({ query: { page: 2, sort: "created_at" } });
// { url: '/posts?page=2&sort=created_at', method: 'get' }

// Merge with existing query string
PostController.index({ mergeQuery: { page: 3 } });
```

### HTTP Method Variants

Routes are generated with method-specific variants:

```typescript
// Default uses the primary method
PostController.index(); // GET
PostController.store(); // POST
PostController.update({ post: 1 }); // PUT
PostController.destroy({ post: 1 }); // DELETE

// Explicit method selection
PostController.index.get();
PostController.index.head();
PostController.update.patch({ post: 1 });
```

### Form-Safe Routes

HTML forms only support GET and POST methods. Wayfinder provides `.form` variants that automatically add Laravel's `_method` field for method spoofing:

```typescript
PostController.update.form({ post: 1 });
// { action: '/posts/1?_method=PATCH', method: 'post' }

PostController.destroy.form({ post: 1 });
// { action: '/posts/1?_method=DELETE', method: 'post' }
```

Use these with HTML forms, for example, in React:

```html
<form {...PostController.update.form({ post: post.id })}>
    <!-- fields -->
</form>
```

## Named Routes

Wayfinder also generates files organized by route names, making it easy to access routes the same way you would with Laravel's `route()` helper.

Given routes defined with names:

```php
Route::get('/posts', [PostController::class, 'index'])->name('posts.index');
Route::get('/posts/{post}', [PostController::class, 'show'])->name('posts.show');
```

Access them by name:

```typescript
import posts from "@/wayfinder/routes/posts";

posts.index();
// { url: '/posts', method: 'get' }

posts.show({ post: 1 });
// { url: '/posts/1', method: 'get' }
```

## Form Request Types

Wayfinder analyzes your Form Request validation rules and generates corresponding TypeScript types.

Given a Form Request:

```php
class StorePostRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'excerpt' => ['nullable', 'string'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string'],
            'meta' => ['nullable', 'array'],
            'meta.description' => ['nullable', 'string'],
        ];
    }
}
```

Wayfinder generates:

```typescript
export type Request = {
    title: string;
    body: string;
    excerpt?: string | null;
    tags?: string[] | null;
    meta?: {
        description?: string | null;
    } | null;
};
```

Import these types from the generated `types.d.ts` file. You can then use them with something like Inertia's [form helper](https://inertiajs.com/docs/v2/the-basics/forms#form-helper):

```typescript
import { App } from "@/wayfinder/types";

const form = useForm<App.Http.Controllers.PostController.Store.Request>();
```

## Eloquent Models

Wayfinder generates TypeScript interfaces for your Eloquent models, including their attributes and relationships.

Given a model:

```php
class User extends Model
{
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_admin' => 'boolean',
    ];

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
```

Wayfinder generates a TypeScript interface in `types.d.ts`:

```typescript
export namespace App.Models {
    export type User = {
        id: number;
        name: string;
        email: string;
        email_verified_at: string | null;
        is_admin: boolean;
        posts: App.Models.Post[];
    };
}
```

Use these types in your frontend:

```typescript
import { App } from "@/wayfinder/types";

function displayUser(user: App.Models.User) {
    console.log(user.name, user.email);
}
```

## PHP Enums

Wayfinder converts PHP enums to TypeScript types and constants.

Given a PHP enum:

```php
enum PostStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
}
```

Wayfinder generates both a type and constants:

```typescript
// Type for type-checking (in types.d.ts)
export namespace App.Enums {
    export type PostStatus = "draft" | "published" | "archived";
}

// Constants for runtime use (in App/Enums/PostStatus.ts)
export const Draft = "draft";
export const Published = "published";
export const Archived = "archived";

export const PostStatus = { Draft, Published, Archived } as const;
```

Use in your TypeScript:

```typescript
import PostStatus from "@/wayfinder/App/Enums/PostStatus";
import { App } from "@/wayfinder/types";

// Use constants
if (post.status === PostStatus.Published) {
    // ...
}

// Type-safe status
function setStatus(status: App.Enums.PostStatus) {
    // Only accepts 'draft', 'published', or 'archived'
}
```

## Inertia.js Integration

Wayfinder provides first-class support for Inertia.js applications, automatically generating types for your page props and shared data.

### Page Props

Given an Inertia controller:

```php
class DashboardController
{
    public function index(): Response
    {
        return Inertia::render('Dashboard', [
            'stats' => [
                'users' => User::count(),
                'posts' => Post::count(),
            ],
            'recentActivity' => Activity::latest()->take(10)->get(),
        ]);
    }
}
```

Wayfinder generates types for the page props:

```typescript
export namespace Inertia.Pages {
    export type Dashboard = Inertia.SharedData & {
        stats: {
            users: number;
            posts: number;
        };
        recentActivity: App.Models.Activity[];
    };
}
```

Use in your Vue or React components:

```vue
<script setup lang="ts">
import { Inertia } from "@/wayfinder/types";

defineProps<Inertia.Pages.Dashboard>();
</script>
```

### Shared Data

Wayfinder also types your shared Inertia data from the `HandleInertiaRequests` middleware:

```typescript
export namespace Inertia {
    export type SharedData = {
        auth: {
            user: App.Models.User | null;
        };
        flash: {
            success: string | null;
            error: string | null;
        };
    };
}
```

## Broadcast Channels

Wayfinder generates type-safe helpers for Laravel broadcast channels.

Given channel definitions in `routes/channels.php`:

```php
Broadcast::channel('orders.{orderId}', function ($user, $orderId) {
    return $user->canViewOrder($orderId);
});

Broadcast::channel('user.{userId}.notifications', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
```

Wayfinder generates:

```typescript
export type BroadcastChannel =
    | `orders.${string | number}`
    | `user.${string | number}.notifications`;

export const BroadcastChannels = {
    orders: (orderId: string | number) => `orders.${orderId}` as const,
    user: (userId: string | number) => ({
        notifications: `user.${userId}.notifications` as const,
    }),
};
```

Use with Laravel Echo:

```typescript
import { BroadcastChannels } from "@/wayfinder/broadcast-channels";
import Echo from "laravel-echo";

// Type-safe channel subscription
Echo.private(BroadcastChannels.orders(orderId)).listen("OrderShipped", (e) => {
    console.log(e.trackingNumber);
});

Echo.private(BroadcastChannels.user(userId).notifications).listen(
    "NewNotification",
    (e) => {
        // ...
    },
);
```

## Broadcast Events

Wayfinder generates types for your broadcast events.

Given a broadcast event:

```php
class OrderShipped implements ShouldBroadcast
{
    public function __construct(
        public int $orderId,
        public string $trackingNumber,
        public string $carrier,
    ) {
        //
    }

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('orders.' . $this->orderId);
    }
}
```

Wayfinder generates:

```typescript
export type BroadcastEvent =
    | ".App.Events.OrderShipped"
    | ".App.Events.UserNotification";

export const BroadcastEvents = {
    "App.Events.OrderShipped": ".App.Events.OrderShipped",
    "App.Events.UserNotification": ".App.Events.UserNotification",
} as const;

// Event payload types in types.d.ts
export namespace App.Events {
    export type OrderShipped = {
        orderId: number;
        trackingNumber: string;
        carrier: string;
    };
}
```

### Laravel Echo Vue/React Integration

If you have `@laravel/echo-vue` or `@laravel/echo-react` installed, Wayfinder generates type augmentations:

```typescript
// echo-broadcast-events.d.ts
declare module "@laravel/echo-vue" {
    interface Events {
        ".App.Events.OrderShipped": {
            orderId: number;
            trackingNumber: string;
            carrier: string;
        };
    }
}
```

This provides full type safety when listening to events:

```typescript
useEcho("orders." + orderId).listen("OrderShipped", (e) => {
    // e is fully typed!
    console.log(e.trackingNumber);
});
```

## Environment Variables

Wayfinder generates TypeScript declarations for your Vite environment variables (those prefixed with `VITE_`).

Given a `.env` file:

```
VITE_APP_NAME="My App"
VITE_API_URL=https://api.example.com
VITE_PUSHER_KEY=your-key
```

Wayfinder generates `vite-env.d.ts`:

```typescript
/// <reference types="vite/client" />

interface ImportMetaEnv {
    readonly VITE_APP_NAME: string;
    readonly VITE_API_URL: string;
    readonly VITE_PUSHER_KEY: string;
}

interface ImportMeta {
    readonly env: ImportMetaEnv;
}
```

This provides autocomplete and type-checking for `import.meta.env`:

```typescript
// TypeScript knows this is a string
const appName = import.meta.env.VITE_APP_NAME;
```

## Configuration

The configuration file is located at `config/wayfinder.php`:

```php
return [
    'generate' => [
        'route' => [
            'actions' => env('WAYFINDER_GENERATE_ROUTE_ACTIONS', true),
            'named' => env('WAYFINDER_GENERATE_NAMED_ROUTES', true),
            'form_variant' => env('WAYFINDER_GENERATE_FORM_VARIANT', true),
            'ignore' => [
                'urls' => [],
                'names' => ['nova.*'],
            ],
        ],
        'models' => env('WAYFINDER_GENERATE_MODELS', true),
        'inertia' => [
            'shared_data' => env('WAYFINDER_GENERATE_INERTIA_SHARED_DATA', true),
        ],
        'broadcast' => [
            'channels' => env('WAYFINDER_GENERATE_BROADCAST_CHANNELS', true),
            'events' => env('WAYFINDER_GENERATE_BROADCAST_EVENTS', true),
        ],
        'environment_variables' => env('WAYFINDER_GENERATE_ENVIRONMENT_VARIABLES', true),
        'enums' => env('WAYFINDER_GENERATE_ENUMS', true),
    ],

    'format' => [
        'enabled' => env('WAYFINDER_FORMAT_ENABLED', false),
    ],

    'cache' => [
        'enabled' => env('WAYFINDER_CACHE_ENABLED', true),
        'directory' => env('WAYFINDER_CACHE_DIRECTORY', storage_path('wayfinder-cache')),
    ],
];
```

### Configuration Options

| Option                           | Description                            | Default                   |
| -------------------------------- | -------------------------------------- | ------------------------- |
| `generate.route.actions`         | Generate controller action files       | `true`                    |
| `generate.route.named`           | Generate named route files             | `true`                    |
| `generate.route.form_variant`    | Include `.form` method variants        | `true`                    |
| `generate.route.ignore.urls`     | URL patterns to ignore                 | `[]`                      |
| `generate.route.ignore.names`    | Route name patterns to ignore          | `['nova.*']`              |
| `generate.models`                | Generate model types                   | `true`                    |
| `generate.inertia.shared_data`   | Generate Inertia shared data types     | `true`                    |
| `generate.broadcast.channels`    | Generate broadcast channel helpers     | `true`                    |
| `generate.broadcast.events`      | Generate broadcast event types         | `true`                    |
| `generate.environment_variables` | Generate Vite env variable types       | `true`                    |
| `generate.enums`                 | Generate PHP enum types                | `true`                    |
| `format.enabled`                 | Format generated files with Biome      | `false`                   |
| `cache.enabled`                  | Enable caching for faster regeneration | `true`                    |
| `cache.directory`                | Directory for cache files              | `storage/wayfinder-cache` |

## Contributing

Thank you for considering contributing to Laravel Wayfinder! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

Please review [our security policy](https://github.com/laravel/wayfinder/security/policy) on how to report security vulnerabilities.

## License

Laravel Wayfinder is open-sourced software licensed under the [MIT license](LICENSE.md).
