import { describe, expect, test } from "vitest";
import { index, show } from "../workbench/resources/js/actions/App/Http/Controllers/SearchController";
import search from "../workbench/resources/js/routes/search";
import photos from "../workbench/resources/js/routes/photos";

describe("query only route", () => {
    test("properties", () => {
        expect(Object.keys(index)).toEqual([
            "definition",
            "url",
            "query",
            "form",
        ]);
    });

    test("definition", () => {
        expect(index.definition).toEqual({
            methods: ["query"],
            url: "/search",
        });
    });

    test("default method", () => {
        expect(index()).toEqual({
            url: "/search",
            method: "query",
        });
    });

    test("query", () => {
        expect(index.query({ query: { page: 2 } })).toEqual({
            url: "/search?page=2",
            method: "query",
        });
    });

    test("default form method spoofs query over post", () => {
        expect(index.form()).toEqual({
            action: "/search?_method=QUERY",
            method: "post",
        });
    });

    test("form query", () => {
        expect(index.form.query({ query: { page: 2 } })).toEqual({
            action: "/search?_method=QUERY&page=2",
            method: "post",
        });
    });

    test("named route", () => {
        expect(search.index()).toEqual({
            url: "/search",
            method: "query",
        });
    });
});

describe("get and query route", () => {
    test("properties", () => {
        expect(Object.keys(show)).toEqual([
            "definition",
            "url",
            "get",
            "query",
            "head",
            "form",
        ]);
    });

    test("default method", () => {
        expect(show("posts")).toEqual({
            url: "/search/posts",
            method: "get",
        });
    });

    test("query", () => {
        expect(show.query("posts")).toEqual({
            url: "/search/posts",
            method: "query",
        });
    });

    test("form get", () => {
        expect(show.form.get("posts")).toEqual({
            action: "/search/posts",
            method: "get",
        });
    });

    test("form query", () => {
        expect(show.form.query("posts")).toEqual({
            action: "/search/posts?_method=QUERY",
            method: "post",
        });
    });

    test("named route", () => {
        expect(search.show.query("posts")).toEqual({
            url: "/search/posts",
            method: "query",
        });
    });
});

describe("redirect route", () => {
    test("every registered verb", () => {
        for (const method of photos.redirect.definition.methods) {
            expect(photos.redirect[method]()).toEqual({
                url: "/old-photos",
                method,
            });
        }
    });
});
