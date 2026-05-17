import { describe, expect, test } from "vitest";
import { readFileSync } from "fs";
import { join } from "path";

describe("FormRequests", () => {
    const typesPath = join(
        __dirname,
        "../workbench/resources/js/wayfinder/types.d.ts"
    );

    const readTypes = () => readFileSync(typesPath, "utf-8");

    test("types.d.ts contains PostController namespace", () => {
        const content = readTypes();

        expect(content).toContain("export namespace PostController");
    });

    test("types.d.ts contains Store Request type", () => {
        const content = readTypes();

        expect(content).toContain("export namespace Store");
        expect(content).toContain("export type Request");
    });

    test("StorePostRequest generates typed scalar, nested, and wildcard fields", () => {
        const content = readTypes();

        expect(content).toContain("title: string");
        expect(content).toContain("body: string");
        expect(content).toContain("excerpt?: string | null");
        expect(content).toContain("published_at?: string | null");
        expect(content).toContain("author_email?: string | null");

        expect(content).toContain("tags?: string[]");
        expect(content).toContain("meta?: {");
        expect(content).toContain("description?: string | null");
        expect(content).toContain("keywords?: string[]");
    });
});