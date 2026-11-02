import { afterEach, expect, test } from "vitest";
import { fixedDomain } from "../workbench/resources/js/actions/App/Http/Controllers/DomainController";
import {
    index,
    show,
    store,
} from "../workbench/resources/js/actions/App/Http/Controllers/PostController";
import { home } from "../workbench/resources/js/routes";
import { setUrlBase } from "../workbench/resources/js/wayfinder";

afterEach(() => setUrlBase(""));

test("it prefixes urls with the url base", () => {
    setUrlBase("/v2");

    expect(index.url()).toBe("/v2/posts");
    expect(show(1)).toEqual({ url: "/v2/posts/1", method: "get" });
    expect(index.url({ query: { page: 2 } })).toBe("/v2/posts?page=2");
    expect(store.form()).toEqual({ action: "/v2/posts", method: "post" });
});

test("it keeps the definition url unprefixed", () => {
    setUrlBase("/v2");

    expect(index.definition.url).toBe("/posts");
});

test("it prefixes the root url without a trailing slash", () => {
    setUrlBase("/v2");

    expect(home.url()).toBe("/v2");
});

test("it ignores a trailing slash on the url base", () => {
    setUrlBase("/v2/");

    expect(index.url()).toBe("/v2/posts");
});

test("it resolves a function url base on every call", () => {
    let base = "/site-a";

    setUrlBase(() => base);

    expect(index.url()).toBe("/site-a/posts");

    base = "/site-b";

    expect(index.url()).toBe("/site-b/posts");
});

test("it does not prefix domain urls", () => {
    setUrlBase("/v2");

    expect(fixedDomain.url({ param: "foo" })).toBe(
        "//example.test/fixed-domain/foo",
    );
});

test("it leaves urls unchanged without a url base", () => {
    expect(index.url()).toBe("/posts");
    expect(home.url()).toBe("/");
});
