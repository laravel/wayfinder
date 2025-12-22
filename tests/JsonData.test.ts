import { existsSync, readFileSync } from "fs";
import { join } from "path";
import { describe, expect, test } from "vitest";

describe("JsonData", () => {
    const typesPath = join(
        __dirname,
        "../workbench/resources/js/wayfinder/types.d.ts"
    );

    test("types.d.ts contains ApiController namespace", () => {
        const content = readFileSync(typesPath, "utf-8");
        expect(content).toContain("export namespace ApiController");
    });

    test.skip("ApiController types directory exists", () => {
        const apiTypesPath = join(
            __dirname,
            "../workbench/resources/js/wayfinder/types/App/Http/Controllers/ApiController"
        );
        expect(existsSync(apiTypesPath)).toBe(true);
    });

    test("has Status method namespace", () => {
        const content = readFileSync(typesPath, "utf-8");
        expect(content).toContain("export namespace Status");
    });

    test("has Users method namespace", () => {
        const content = readFileSync(typesPath, "utf-8");
        expect(content).toContain("export namespace Users");
    });
});
