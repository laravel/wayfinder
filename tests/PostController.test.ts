import { describe, expect, test } from "vitest";
import {
    create,
    destroy,
    edit,
    index,
    show,
    store,
    update,
} from "../workbench/resources/js/actions/App/Http/Controllers/PostController";

describe("index", async () => {
    test("properties", () => {
        expect(Object.keys(index)).toEqual([
            "definition",
            "url",
            "get",
            "head",
            "form",
        ]);
    });

    test("url", () => {
        expect(index.url()).toBe("/posts");
    });

    test("default method", () => {
        expect(index()).toEqual({
            uri: "/posts",
            method: "get",
        });
    });

    test("get", () => {
        expect(index.get()).toEqual({
            uri: "/posts",
            method: "get",
        });
    });

    test("head", () => {
        expect(index.head()).toEqual({
            uri: "/posts",
            method: "head",
        });
    });

    test("default form method", () => {
        expect(index.form()).toEqual({
            action: "/posts",
            method: "get",
        });
    });

    test("form get", () => {
        expect(index.form.get()).toEqual({
            action: "/posts",
            method: "get",
        });
    });

    test("form head", () => {
        expect(index.form.head()).toEqual({
            action: "/posts?_method=HEAD",
            method: "get",
        });
    });

    test("definition", () => {
        expect(Object.keys(index.definition)).toEqual(["methods", "uri"]);
        expect(index.definition.methods).toEqual(["get", "head"]);
        expect(index.definition.uri).toBe("/posts");
    });
});

describe("create", async () => {
    test("properties", () => {
        expect(Object.keys(create)).toEqual([
            "definition",
            "url",
            "get",
            "head",
            "form",
        ]);
    });

    test("url", () => {
        expect(create.url()).toBe("/posts/create");
    });

    test("default method", () => {
        expect(create()).toEqual({
            uri: "/posts/create",
            method: "get",
        });
    });

    test("get", () => {
        expect(create.get()).toEqual({
            uri: "/posts/create",
            method: "get",
        });
    });

    test("head", () => {
        expect(create.head()).toEqual({
            uri: "/posts/create",
            method: "head",
        });
    });

    test("definition", () => {
        expect(Object.keys(create.definition)).toEqual(["methods", "uri"]);
        expect(create.definition.methods).toEqual(["get", "head"]);
        expect(create.definition.uri).toBe("/posts/create");
    });
});

describe("store", async () => {
    test("properties", () => {
        expect(Object.keys(store)).toEqual([
            "definition",
            "url",
            "post",
            "form",
        ]);
    });

    test("url", () => {
        expect(store.url()).toBe("/posts");
    });

    test("default method", () => {
        expect(store()).toEqual({
            uri: "/posts",
            method: "post",
        });
    });

    test("post", () => {
        expect(store.post()).toEqual({
            uri: "/posts",
            method: "post",
        });
    });

    test("definition", () => {
        expect(Object.keys(store.definition)).toEqual(["methods", "uri"]);
        expect(store.definition.methods).toEqual(["post"]);
        expect(store.definition.uri).toBe("/posts");
    });
});

describe("show", async () => {
    test("properties", () => {
        expect(Object.keys(show)).toEqual([
            "definition",
            "url",
            "get",
            "head",
            "form",
        ]);
    });

    test("url", () => {
        expect(show.url({ post: 1 })).toBe("/posts/1");
    });

    test("default method", () => {
        expect(show({ post: 1 })).toEqual({
            uri: "/posts/1",
            method: "get",
        });
    });

    test("get", () => {
        expect(show.get({ post: 1 })).toEqual({
            uri: "/posts/1",
            method: "get",
        });
    });

    test("head", () => {
        expect(show.head({ post: 1 })).toEqual({
            uri: "/posts/1",
            method: "head",
        });
    });

    test("definition", () => {
        expect(Object.keys(show.definition)).toEqual(["methods", "uri"]);
        expect(show.definition.methods).toEqual(["get", "head"]);
        expect(show.definition.uri).toBe("/posts/{post}");
    });
});

describe("edit", async () => {
    test("properties", () => {
        expect(Object.keys(edit)).toEqual([
            "definition",
            "url",
            "get",
            "head",
            "form",
        ]);
    });

    test("url", () => {
        expect(edit.url({ post: 1 })).toBe("/posts/1/edit");
    });

    test("default method", () => {
        expect(edit({ post: 1 })).toEqual({
            uri: "/posts/1/edit",
            method: "get",
        });
    });

    test("get", () => {
        expect(edit.get({ post: 1 })).toEqual({
            uri: "/posts/1/edit",
            method: "get",
        });
    });

    test("head", () => {
        expect(edit.head({ post: 1 })).toEqual({
            uri: "/posts/1/edit",
            method: "head",
        });
    });

    test("definition", () => {
        expect(Object.keys(edit.definition)).toEqual(["methods", "uri"]);
        expect(edit.definition.methods).toEqual(["get", "head"]);
        expect(edit.definition.uri).toBe("/posts/{post}/edit");
    });
});

describe("update", async () => {
    test("properties", () => {
        expect(Object.keys(update)).toEqual([
            "definition",
            "url",
            "patch",
            "form",
        ]);
    });

    test("url", () => {
        expect(update.url({ post: 1 })).toBe("/posts/1");
    });

    test("default method", () => {
        expect(update({ post: 1 })).toEqual({
            uri: "/posts/1",
            method: "patch",
        });
    });

    test("patch", () => {
        expect(update.patch({ post: 1 })).toEqual({
            uri: "/posts/1",
            method: "patch",
        });
    });

    test("definition", () => {
        expect(Object.keys(update.definition)).toEqual(["methods", "uri"]);
        expect(update.definition.methods).toEqual(["patch"]);
        expect(update.definition.uri).toBe("/posts/{post}");
    });
});

describe("destroy", async () => {
    test("properties", () => {
        expect(Object.keys(destroy)).toEqual([
            "definition",
            "url",
            "delete",
            "form",
        ]);
    });

    test("url", () => {
        expect(destroy.url({ post: 1 })).toBe("/posts/1");
    });

    test("default method", () => {
        expect(destroy({ post: 1 })).toEqual({
            uri: "/posts/1",
            method: "delete",
        });
    });

    test("delete", () => {
        expect(destroy.delete({ post: 1 })).toEqual({
            uri: "/posts/1",
            method: "delete",
        });
    });

    test("definition", () => {
        expect(Object.keys(destroy.definition)).toEqual(["methods", "uri"]);
        expect(destroy.definition.methods).toEqual(["delete"]);
        expect(destroy.definition.uri).toBe("/posts/{post}");
    });
});
