import { readFileSync } from "fs";
import { join } from "path";
import { describe, expect, expectTypeOf, test } from "vitest";
import type { Closure } from "../workbench/resources/js/wayfinder/types";

describe("closure route types", () => {
    const types = readFileSync(
        join(__dirname, "../workbench/resources/js/wayfinder/types.d.ts"),
        "utf-8",
    );

    test("each named closure route gets its own namespace from its name", () => {
        expectTypeOf<Closure.ClosureJson.First.Request>().toEqualTypeOf<Record<string, unknown>>();
        expectTypeOf<Closure.ClosureJson.Second.Request>().toEqualTypeOf<Record<string, unknown>>();
        expectTypeOf<Closure.Projects.Application.CustomerSector.Request>().toEqualTypeOf<
            Record<string, unknown>
        >();
    });

    test("closure routes no longer share a single __invoke type", () => {
        const closureNamespace = types.slice(types.indexOf("export namespace Closure {"));

        expect(closureNamespace).not.toMatch(/^    export namespace __invoke/m);
        expect(types).not.toContain("Closure::__invoke");
    });

    // Cached routes serialize closures, which leaves no file to point at.
    test.skipIf(!!process.env.WAYFINDER_CACHE_ROUTES)(
        "closure types point at the line that defines the route",
        () => {
            expect(types).toMatch(/@see \S+routes\/web\.php:\d+\s*\*\/\s*export type Request/);
        },
    );

    test("no reference points at a placeholder path", () => {
        expect(types).not.toContain("[serialized-closure]");
        expect(types).not.toContain("[unknown]");
    });
});
