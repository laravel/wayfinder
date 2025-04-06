import { expect, it } from "vitest";
import { index } from "../workbench/resources/js/actions/App/Http/Controllers/PostController";

it("can convert basic params", () => {
    expect(
        index({
            query: {
                foo: "bar",
                bar: "baz",
            },
        }),
    ).toEqual({
        url: "/posts?foo=bar&bar=baz",
        method: "get",
    });
});

it("can convert array params", () => {
    expect(
        index({
            query: {
                foo: ["bar", "baz"],
                bar: "qux",
            },
        }),
    ).toEqual({
        url: "/posts?foo%5B%5D=bar&foo%5B%5D=baz&bar=qux",
        method: "get",
    });
});

it("can convert object params", () => {
    expect(
        index({
            query: {
                foo: {
                    a: "baz",
                    b: "qux",
                },
                bar: "something",
            },
        }),
    ).toEqual({
        url: "/posts?foo%5Ba%5D=baz&foo%5Bb%5D=qux&bar=something",
        method: "get",
    });
});

it("can convert boolean params", () => {
    expect(
        index({
            query: {
                foo: true,
                bar: false,
            },
        }),
    ).toEqual({
        url: "/posts?foo=1&bar=0",
        method: "get",
    });
});

it("will ignore existing params without star", () => {
    window.location.search = "?foo=bar&bar=baz";

    expect(
        index({
            query: {
                also: "yes",
                bar: "no",
            },
        }),
    ).toEqual({
        url: "/posts?also=yes&bar=no",
        method: "get",
    });
});

it("can integrate basic params with existing window params", () => {
    window.location.search = "?foo=bar&bar=baz";

    expect(
        index({
            mergeQuery: {
                also: "yes",
                bar: "no",
            },
        }),
    ).toEqual({
        url: "/posts?foo=bar&bar=no&also=yes",
        method: "get",
    });
});

it("can integrate array params with existing window params", () => {
    window.location.search = "?foo[]=bar&bar=baz";

    expect(
        index({
            mergeQuery: {
                foo: ["qux", "baz"],
                also: "yes",
            },
        }),
    ).toEqual({
        url: "/posts?bar=baz&foo%5B%5D=qux&foo%5B%5D=baz&also=yes",
        method: "get",
    });
});

it("can integrate object params with existing window params", () => {
    window.location.search = "?foo[baz]=bar&something=else";

    expect(
        index({
            mergeQuery: {
                foo: { qux: "baz" },
                also: "yes",
            },
        }),
    ).toEqual({
        url: "/posts?something=else&foo%5Bqux%5D=baz&also=yes",
        method: "get",
    });
});

it("can delete existing params via null", () => {
    window.location.search = "?foo=bar&bar=baz";

    expect(
        index({
            mergeQuery: {
                foo: null,
            },
        }),
    ).toEqual({
        url: "/posts?bar=baz",
        method: "get",
    });
});

it("can delete existing params via undefined", () => {
    window.location.search = "?foo=bar&bar=baz";

    expect(
        index({
            mergeQuery: {
                foo: undefined,
            },
        }),
    ).toEqual({
        url: "/posts?bar=baz",
        method: "get",
    });
});

it("can merge with the form method", () => {
    window.location.search = "?foo=bar&bar=baz";

    expect(
        index.form.head({
            mergeQuery: {
                foo: "sure",
            },
        }),
    ).toEqual({
        action: "/posts?foo=sure&bar=baz&_method=HEAD",
        method: "get",
    });
});

it("ignores nested object values with unallowed types", () => {
    window.location.search = "?parent=og";

    const query = (): {
        string: string;
        number: number;
        boolean: boolean;
    } => {
        const obj = {
            string: 'string',
            number: 5,
            boolean: true,
            undefined: undefined,
            null: null,
            array: [],
            object: {},
        }

        return obj
    }

    expect(
        index.form.head({
            mergeQuery: {
                parent: query(),
            },
        }),
    ).toEqual({
        action: "/posts?parent=og&_method=HEAD&parent%5Bstring%5D=string&parent%5Bnumber%5D=5&parent%5Bboolean%5D=1",
        method: "get",
    });
});
