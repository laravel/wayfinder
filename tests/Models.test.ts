import { existsSync, readFileSync } from "fs";
import { join } from "path";
import { describe, expect, test } from "vitest";

describe("Models", () => {
    const typesPath = join(
        __dirname,
        "../workbench/resources/js/wayfinder/types.d.ts"
    );

    test("types.d.ts contains App.Models namespace", () => {
        const content = readFileSync(typesPath, "utf-8");
        expect(content).toContain("export namespace App");
        expect(content).toContain("export namespace Models");
    });

    test("types.d.ts contains User model type", () => {
        const content = readFileSync(typesPath, "utf-8");
        expect(content).toContain("export type User");
    });

    test("User model includes Attribute-based computed property", () => {
        const content = readFileSync(typesPath, "utf-8");
        expect(content).toMatch(/formatted_name:\s*string/);
    });

    test("User model includes Attribute-based computed property", () => {
        const content = readFileSync(typesPath, "utf-8");
        expect(content).toMatch(/without_doc:\s*unknown/);
    });

    test.skip("User model types directory exists", () => {
        const userTypesPath = join(
            __dirname,
            "../workbench/resources/js/wayfinder/types/App/Models/User"
        );
        expect(existsSync(userTypesPath)).toBe(true);
    });
});
