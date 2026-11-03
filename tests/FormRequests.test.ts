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

    type Store = App.Http.Controllers.PostController.Store.Request;

    test("wildcard rules type the array's elements", () => {
        expectTypeOf<Store["tags"]>().toEqualTypeOf<string[] | null | undefined>();
        expectTypeOf<Store["matrix"]>().toEqualTypeOf<number[][] | undefined>();
    });

    test("dotted rules nest into objects", () => {
        expectTypeOf<Store["meta"]>().toEqualTypeOf<
            { description?: string | null; keywords?: string[] | null } | null | undefined
        >();
        expectTypeOf<Store["seo"]>().toEqualTypeOf<{ title: string; slug?: string | null }>();
        expectTypeOf<Store["authors"]>().toEqualTypeOf<{ name: string; email?: string | null }[]>();
    });

    test("no dotted keys leak into the type", () => {
        expectTypeOf<Store>().not.toHaveProperty("tags.*");
        expectTypeOf<Store>().not.toHaveProperty("meta.description");
    });

    test("file rules become File", () => {
        expectTypeOf<Store["cover"]>().toEqualTypeOf<File | null | undefined>();
        expectTypeOf<Store["attachments"]>().toEqualTypeOf<File[] | undefined>();
    });

    test("enum rules use the enum's type, even beside a string rule", () => {
        expectTypeOf<Store["state"]>().toEqualTypeOf<App.Enums.PostStatus>();
        expectTypeOf<Store["priority"]>().toEqualTypeOf<App.Enums.Priority | null | undefined>();
    });

    test("an in rule with values that can't be resolved falls back to its other rules", () => {
        expectTypeOf<Store["channel"]>().toEqualTypeOf<string>();
    });

    test("rules that don't pin a type stay unknown", () => {
        expectTypeOf<Store["notes"]>().toBeUnknown();
    });

    test("StorePostRequest generates typed fields", () => {
        const content = readFileSync(typesPath, "utf-8");
        // The Request type should include fields from StorePostRequest
        expect(content).toContain("title");
        expect(content).toContain("body");
    });
});
