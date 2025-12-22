import { existsSync, readFileSync } from "fs";
import { join } from "path";
import { describe, expect, test } from "vitest";

describe("InertiaData", () => {
    const typesPath = join(
        __dirname,
        "../workbench/resources/js/wayfinder/types.d.ts"
    );

    test("types.d.ts contains Inertia namespace", () => {
        const content = readFileSync(typesPath, "utf-8");
        expect(content).toContain("export namespace Inertia");
    });

    test("types.d.ts contains Pages namespace for Inertia", () => {
        const content = readFileSync(typesPath, "utf-8");
        expect(content).toContain("export namespace Pages");
    });

    test("has Dashboard page type", () => {
        const content = readFileSync(typesPath, "utf-8");
        expect(content).toContain("Dashboard");
    });

    test.skip("InertiaController action types directory exists", () => {
        const inertiaTypesPath = join(
            __dirname,
            "../workbench/resources/js/wayfinder/types/App/Http/Controllers/InertiaController"
        );
        expect(existsSync(inertiaTypesPath)).toBe(true);
    });
});
