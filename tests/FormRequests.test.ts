import { describe, expect, expectTypeOf, test } from "vitest";
import { readFileSync } from "fs";
import { join } from "path";
import type { App } from "../workbench/resources/js/wayfinder/types";

describe("FormRequests", () => {
    const typesPath = join(
        __dirname,
        "../workbench/resources/js/wayfinder/types.d.ts"
    );

    test("types.d.ts contains PostController namespace", () => {
        const content = readFileSync(typesPath, "utf-8");
        expect(content).toContain("export namespace PostController");
    });

    test("types.d.ts contains Store Request type", () => {
        const content = readFileSync(typesPath, "utf-8");
        expect(content).toContain("export namespace Store");
        expect(content).toContain("export type Request");
    });

    test("array rules with keys become an object of optional keys", () => {
        expectTypeOf<
            App.Http.Controllers.PostController.Store.Request["settings"]
        >().toEqualTypeOf<
            { theme?: unknown; timezone?: unknown } | null | undefined
        >();
    });

    test("StorePostRequest generates typed fields", () => {
        const content = readFileSync(typesPath, "utf-8");
        // The Request type should include fields from StorePostRequest
        expect(content).toContain("title");
        expect(content).toContain("body");
    });
});
