<p align="center"><img src="./art/logo.svg" width="50%" alt="Laravel Wayfinder Logo"></p>

## Introduction

Laravel Wayfinder bridges your Laravel backend and TypeScript frontend with zero friction. It automatically generates fully-typed, importable TypeScript functions for your controllers and routes — so you can call your Laravel endpoints directly in your client code just like any other function. No more hardcoding URLs, guessing route parameters, or syncing backend changes manually.

> [!IMPORTANT]
> Wayfinder is currently in Beta, the API is subject to change prior to the v1.0.0 release. All notable changes will be documented in the [changelog](./CHANGELOG.md).

## Installation

To get started, install Wayfinder via the Composer package manager:

```
composer require laravel/wayfinder
```

Next, install the [Wayfinder Vite plugin](https://github.com/laravel/vite-plugin-wayfinder) to ensure that your routes are generated during Vite's build step and also whenever your files change while running the Vite's dev server.

First, install the plugin via NPM:

```
npm i -D @laravel/vite-plugin-wayfinder
```

Then, update your application's `vite.config.js` file to watch for changes to your application's routes and controllers:

```ts
import { wayfinder } from "@laravel/vite-plugin-wayfinder";

export default defineConfig({
    plugins: [
        wayfinder(),
        // ...
    ],
});
```

You can read about all of the plugin's configuration options in the [documentation](https://github.com/laravel/vite-plugin-wayfinder).

## Generating TypeScript Definitions

The `wayfinder:generate` command can be used to generate TypeScript definitions for your routes and controller methods:

```
php artisan wayfinder:generate
```

By default, Wayfinder generates files in three directories (`wayfinder`, `actions`, and `routes`) within `resources/js`, but you can configure the base path:

```
php artisan wayfinder:generate --path=resources/js/wayfinder
```

The `--skip-actions` and `--skip-routes` options may be used to skip TypeScript definition generation for controller methods or routes, respectively:

```
php artisan wayfinder:generate --skip-actions
php artisan wayfinder:generate --skip-routes
```

You can safely `.gitignore` the `wayfinder`, `actions`, and `routes` directories as they are completely re-generated on every build.

## Usage

Wayfinder functions return an object that contains the resolved URL and default HTTP method:

```ts
import { show } from "@/actions/App/Http/Controllers/PostController";

show(1); // { url: "/posts/1", method: "get" }
```

If you just need the URL, or would like to choose a method from the HTTP methods defined on the server, you can invoke additional methods on the Wayfinder generated function:

```ts
import { show } from "@/actions/App/Http/Controllers/PostController";

show.url(1); // "/posts/1"
show.head(1); // { url: "/posts/1", method: "head" }
```

Wayfinder functions accept a variety of shapes for their arguments:

```ts
import { show, update } from "@/actions/App/Http/Controllers/PostController";

// Single parameter action...
show(1);
show({ id: 1 });

// Multiple parameter action...
update([1, 2]);
update({ post: 1, author: 2 });
update({ post: { id: 1 }, author: { id: 2 } });
```

> [!NOTE]
> If you are using a JavaScript [reserved word](https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Lexical_grammar#reserved_words) such as `delete` or `import`, as a method in your controller, Wayfinder will rename it to `[method name]Method` (`deleteMethod`, `importMethod`) when generating its functions. This is because these words are not allowed as variable declarations in JavaScript.

If you've specified a key for the parameter binding, Wayfinder will detect this and allow you to pass the value in as a property on an object:

```ts
import { show } from "@/actions/App/Http/Controllers/PostController";

// Route is /posts/{post:slug}...
show("my-new-post");
show({ slug: "my-new-post" });
```

### Invokable Controllers

If your controller is an invokable controller, you may simply invoke the imported Wayfinder function directly:

```ts
import StorePostController from "@/actions/App/Http/Controllers/StorePostController";

StorePostController();
```

### Importing Controllers

You may also import the Wayfinder generated controller definition and invoke its individual methods on the imported object:

```ts
import PostController from "@/actions/App/Http/Controllers/PostController";

PostController.show(1);
```

> [!NOTE]
> In the example above, importing the entire controller prevents the `PostController` from being tree-shaken, so all `PostController` actions will be included in your final bundle.

### Importing Named Routes

Wayfinder can also generate methods for your application's named routes as well:

```ts
import { show } from "@/routes/post";

// Named route is `post.show`...
show(1); // { url: "/posts/1", method: "get" }
```

### Conventional Forms

If your application uses conventional HTML form submissions, Wayfinder can help you out there as well. First, opt into form variants when generating your TypeScript definitions:

```shell
php artisan wayfinder:generate --with-form
```

Then, you can use the `.form` variant to generate `<form>` object attributes automatically:

```tsx
import { store, update } from "@/actions/App/Http/Controllers/PostController";

const Page = () => (
    <form {...store.form()}>
        {/* <form action="/posts" method="post"> */}
        {/* ... */}
    </form>
);

const Page = () => (
    <form {...update.form(1)}>
        {/* <form action="/posts/1?_method=PATCH" method="post"> */}
        {/* ... */}
    </form>
);
```

If your form action supports multiple methods and would like to specify a method, you can invoke additional methods on the `form`:

```tsx
import { store, update } from "@/actions/App/Http/Controllers/PostController";

const Page = () => (
    <form {...update.form.put(1)}>
        {/* <form action="/posts/1?_method=PUT" method="post"> */}
        {/* ... */}
    </form>
);
```

## Query Parameters

All Wayfinder methods accept an optional, final `options` argument to which you may pass a `query` object. This object can be used to append query parameters onto the resulting URL:

```ts
import { show } from "@/actions/App/Http/Controllers/PostController";

const options = {
    query: {
        page: 1,
        sort_by: "name",
    },
};

show(1, options); // { url: "/posts/1?page=1&sort_by=name", method: "get" }
show.get(1, options); // { url: "/posts/1?page=1&sort_by=name", method: "get" }
show.url(1, options); // "/posts/1?page=1&sort_by=name"
show.form.head(1, options); // { action: "/posts/1?page=1&sort_by=name&_method=HEAD", method: "get" }
```

You can also merge with the URL's existing parameters by passing a `mergeQuery` object instead:

```ts
import { show } from "@/actions/App/Http/Controllers/PostController";

// window.location.search = "?page=1&sort_by=category&q=shirt"

const options = {
    mergeQuery: {
        page: 2,
        sort_by: "name",
    },
};

show.url(1, options); // "/posts/1?page=2&sort_by=name&q=shirt"
```

If you would like to remove a parameter from the resulting URL, define the value as `null` or `undefined`:

```ts
import { show } from "@/actions/App/Http/Controllers/PostController";

// window.location.search = "?page=1&sort_by=category&q=shirt"

const options = {
    mergeQuery: {
        page: 2,
        sort_by: null,
    },
};

show.url(1, options); // "/posts/1?page=2&q=shirt"
```

## Wayfinder and Inertia

When using [Inertia](https://inertiajs.com), you can pass the result of a Wayfinder method directly to the `submit` method of `useForm`, it will automatically resolve the correct URL and method:

[https://inertiajs.com/forms#wayfinder](https://inertiajs.com/forms#wayfinder)

```ts
import { useForm } from "@inertiajs/react";
import { store } from "@/actions/App/Http/Controllers/PostController";

const form = useForm({
    name: "My Big Post",
});

form.submit(store()); // Will POST to `/posts`...
```

You may also use Wayfinder in conjunction with Inertia's `Link` component:

[https://inertiajs.com/links#wayfinder](https://inertiajs.com/links#wayfinder)

```tsx
import { Link } from "@inertiajs/react";
import { show } from "@/actions/App/Http/Controllers/PostController";

const Nav = () => <Link href={show(1)}>Show me the first post</Link>;
```

## Contributing

Thank you for considering contributing to Wayfinder! You can read the contribution guide [here](.github/CONTRIBUTING.md).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

Please review [our security policy](https://github.com/laravel/wayfinder/security/policy) on how to report security vulnerabilities.

## License

Wayfinder is open-sourced software licensed under the [MIT license](LICENSE.md).
