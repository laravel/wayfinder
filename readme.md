# Laravel Wayfinder

Laravel Wayfinder bridges your Laravel backend and TypeScript frontend with zero friction. It automatically generates fully-typed, importable TypeScript functions for your controllers and routes — so you can call your Laravel endpoints directly in your client code just like any other function. No more hardcoding URLs, guessing route parameters, or syncing backend changes manually.

## Installation

To get started, install Wayfinder via the Composer package manager:

```
composer require laravel/wayfinder
```

If you would like to automatically watch your files for changes, you may use `vite-plugin-run`:

```
npm i -D vite-plugin-run
```

Then, in your `vite.config.js`:

```ts
import { run } from "vite-plugin-run";

export default defineConfig({
    plugins: [
        // ...
        run([
            {
                name: "wayfinder",
                run: ["php", "artisan", "wayfinder:generate"],
                pattern: ["routes/*.php", "app/**/Http/**/*.php"],
            },
        ]),
    ],
});
```

For convenience, you may also wish to register aliases for importing the generated files into your application:

```ts
export default defineConfig({
    // ...
    resolve: {
        alias: {
            "@actions/": "./resources/js/actions",
            "@routes/": "./resources/js/routes",
        },
    },
});
```

## Generating TypeScript Definitions

The `wayfinder:generate` command can be used to generate TypeScript definitions for your routes and controller methods:

```
php artisan wayfinder:generate
```

By default, Wayfinder generates files in three directories (`wayfinder`, `actions`, and `routes`) within `resources/js`, but you can configure the base path:

```
php artisan wayfinder:generate --base=resources/js/wayfinder
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
import { show } from "@actions/App/Http/Controllers/PostController";

show(1); // { uri: "/posts/1", method: "get" }
```

If you just need the URL, or would like to choose a method from the HTTP methods defined on the server, you can invoke additional methods on the Wayfinder generated function:

```ts
import { show } from "@actions/App/Http/Controllers/PostController";

show.url(1); // "/posts/1"
show.head(1); // { uri: "/posts/1", method: "head" }
```

Wayfinder functions accept a variety of shapes for their arguments:

```ts
import { show, update } from "@actions/App/Http/Controllers/PostController";

// Single parameter action...
show(1);
show({ id: 1 });

// Multiple parameter action...
update([1, 2]);
update({ post: 1, author: 2 });
update({ post: { id: 1 }, author: { id: 2 } });
```

**Note:** If you have a `delete` method on your controller, Wayfinder will rename it to `deleteMethod` when generating its functions. This is because `delete` is not allowed as a variable declaration.

If you've specified a key for the parameter binding, Wayfinder will detect this and allow you to pass the value in as a property on an object:

```ts
import { show } from "@actions/App/Http/Controllers/PostController";

// Route is /posts/{post:slug}...
show("my-new-post");
show({ slug: "my-new-post" });
```

### Invokable Controllers

If your controller is an invokable controller, you may simple invoke the imported Wayfinder function directly:

```ts
import StorePostController from "@actions/App/Http/Controllers/StorePostController";

StorePostController();
```

### Importing Controllers

You may also import the Wayfinder generated controller definition and invoke its individual methods on the imported object:

```ts
import PostController from "@actions/App/Http/Controllers/PostController";

PostController.show(1);
```

**Note:** In the example above, importing the entire controller prevents the `PostController` from being tree-shaken, so all `PostController` actions will be included in your final bundle.

### Importing Named Routes

Wayfinder can also generate methods for your application's named routes as well:

```ts
import { show } from "@routes/post";

// Named route is `post.show`...
show(1); // { uri: "/posts/1", method: "get" }
```

### Conventional Forms

If your application uses conventional HTML form submissions, Wayfinder can help you out there as well. First, opt into form variants when generating your TypeScript definitions:

```shell
php artisan wayfinder:generate --with-form
```

Then, you can use the `.form` variant to generate `<form>` object attributes automatically:

```tsx
import { store, update } from "@actions/App/Http/Controllers/PostController";

const Page = () => (
    <form {...store.form(1)}>
        {" "}
        {/* { action: "/posts", method: "post" } */}
        {/* ... */}
    </form>
);

const Page = () => (
    <form {...update.form.patch(1)}>
        {" "}
        {/* { action: "/posts/1?_method=PATCH", method: "post" } */}
        {/* ... */}
    </form>
);
```

## Query Parameters

All Wayfinder methods accept an optional, final `options` argument to which you may pass a `query` object. This object can be used to append query parameters onto the resulting URL:

```ts
import { show } from "@actions/App/Http/Controllers/PostController";

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
import { show } from "@actions/App/Http/Controllers/PostController";

// window.location.search = "?page=1&sort_by=category&q=shirt

const options = {
    mergeQuery: {
        page: 2,
        sort_by: "name",
    },
};

show.url(1, options); // "/posts/1?page=2&sort_by=name&q=shirt
```

If you would like to remove a parameter from the resulting URL, define the value as `null` or `undefined`:

```ts
import { show } from "@actions/App/Http/Controllers/PostController";

// window.location.search = "?page=1&sort_by=category&q=shirt

const options = {
    mergeQuery: {
        page: 2,
        sort_by: null,
    },
};

show.url(1, options); // "/posts/1?page=2q=shirt
```

## Wayfinder and Inertia

When using [Inertia](https://inertiajs.com), you can pass the result of a Wayfinder method directly to the `submit` method of `useForm`, it will automatically resolve the correct URL and method:

```ts
import { useForm } from "@inertiajs/react";
import { store } from "@actions/App/Http/Controllers/PostController";

const form = useForm({
    name: "My Big Post",
});

form.submit(store()); // Will POST to `/posts`...
```

You may also use Wayfinder in conjunction with Inertia's `Link` component:

```tsx
import { Link } from "@inertiajs/react";
import { show } from "@actions/App/Http/Controllers/PostController";

const Nav = () => <Link href={show(1)}>Show me the first post</Link>;
```
