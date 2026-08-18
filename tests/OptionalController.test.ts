import { describe, expect, it, test } from "vitest";
import {
    emptyDefault,
    manyOptional,
    optional,
} from "../workbench/resources/js/actions/App/Http/Controllers/OptionalController";

describe("optional", async () => {
    test("url", () => {
        expect(optional.url()).toBe("/optional");
        expect(optional.url({ parameter: "xxxx" })).toBe("/optional/xxxx");
    });

    test("definition", () => {
        expect(optional.definition.url).toBe("/optional/{parameter?}");
    });
});

describe("manyOptional", async () => {
    test("url", () => {
        expect(manyOptional.url()).toBe("/many-optional");
        expect(manyOptional.url({ one: "1" })).toBe("/many-optional/1");
        expect(manyOptional.url({ one: "1", two: "2" })).toBe(
            "/many-optional/1/2",
        );
        expect(manyOptional.url({ one: "1", two: "2", three: "3" })).toBe(
            "/many-optional/1/2/3",
        );
    });

    test("url supports falsy optional values", () => {
        expect(manyOptional.url({ one: 0, two: 2 })).toBe("/many-optional/0/2");
        expect(manyOptional.url({ one: 0 })).toBe("/many-optional/0");
    });

    it("throws an error when passing optional parameters with missing optional parameters before", () => {
        expect(() => manyOptional.url({ two: "2" })).toThrow();
        expect(() => manyOptional.url({ three: "3" })).toThrow();
        expect(() => manyOptional.url({ two: "2", three: "3" })).toThrow();
    });

    test("definition", () => {
        expect(manyOptional.definition.url).toBe(
            "/many-optional/{one?}/{two?}/{three?}",
        );
    });
});

describe("emptyDefault", async () => {
    it("throws rather than silently producing a hole when an empty-default parameter is skipped ahead of a real optional one", () => {
        expect(() => emptyDefault.url({ slug: "x" })).toThrow();
    });

    it("still produces a double slash when every optional segment (including the empty-default one) is omitted, a pre-existing limitation of trailing-optional URL building", () => {
        expect(emptyDefault.url()).toBe("/empty-default//thing");
    });

    test("url", () => {
        expect(
            emptyDefault.url({ emptyUrlDefault: "acme", slug: "x" }),
        ).toBe("/empty-default/acme/thing/x");
    });
});
