import { expect, it } from "vitest";
import { index } from "../workbench/resources/js/actions/App/Http/Controllers/PostController";

it("can convert basic params", () => {
    expect(
        index({
            foo: "bar",
            bar: "baz",
        }),
    ).toEqual({
        uri: "/posts?foo=bar&bar=baz",
        method: "get",
    });
});

it("can convert array params", () => {
    expect(
        index({
            foo: ["bar", "baz"],
            bar: "qux",
        }),
    ).toEqual({
        uri: "/posts?foo%5B%5D=bar&foo%5B%5D=baz&bar=qux",
        method: "get",
    });
});

it("can convert object params", () => {
    expect(
        index({
            foo: {
                a: "baz",
                b: "qux",
            },
            bar: "something",
        }),
    ).toEqual({
        uri: "/posts?foo%5Ba%5D=baz&foo%5Bb%5D=qux&bar=something",
        method: "get",
    });
});

it("can convert boolean params", () => {
    expect(
        index({
            foo: true,
            bar: false,
        }),
    ).toEqual({
        uri: "/posts?foo=1&bar=0",
        method: "get",
    });
});

it("will ignore existing params without star", () => {
    window.location.search = "?foo=bar&bar=baz";

    expect(
        index({
            also: "yes",
            bar: "no",
        }),
    ).toEqual({
        uri: "/posts?also=yes&bar=no",
        method: "get",
    });
});

it("can integrate basic params with existing window params", () => {
    window.location.search = "?foo=bar&bar=baz";

    expect(
        index({
            "*": true,
            also: "yes",
            bar: "no",
        }),
    ).toEqual({
        uri: "/posts?foo=bar&bar=no&also=yes",
        method: "get",
    });
});

it("can integrate array params with existing window params", () => {
    window.location.search = "?foo[]=bar&bar=baz";

    expect(
        index({
            "*": true,
            foo: ["qux", "baz"],
            also: "yes",
        }),
    ).toEqual({
        uri: "/posts?bar=baz&foo%5B%5D=qux&foo%5B%5D=baz&also=yes",
        method: "get",
    });
});

it("can integrate object params with existing window params", () => {
    window.location.search = "?foo[baz]=bar&something=else";

    expect(
        index({
            "*": true,
            foo: { qux: "baz" },
            also: "yes",
        }),
    ).toEqual({
        uri: "/posts?something=else&foo%5Bqux%5D=baz&also=yes",
        method: "get",
    });
});

it("can delete existing params via null", () => {
    window.location.search = "?foo=bar&bar=baz";

    expect(
        index({
            "*": true,
            foo: null,
        }),
    ).toEqual({
        uri: "/posts?bar=baz",
        method: "get",
    });
});

it("can delete existing params via undefined", () => {
    window.location.search = "?foo=bar&bar=baz";

    expect(
        index({
            "*": true,
            foo: undefined,
        }),
    ).toEqual({
        uri: "/posts?bar=baz",
        method: "get",
    });
});

it("can merge with the form method", () => {
    window.location.search = "?foo=bar&bar=baz";

    expect(
        index.form.head({
            "*": true,
            foo: "sure",
        }),
    ).toEqual({
        action: "/posts?foo=sure&bar=baz&_method=HEAD",
        method: "get",
    });
});
