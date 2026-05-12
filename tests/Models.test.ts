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

    test("User type omits attributes listed in $hidden", () => {
        const content = readFileSync(typesPath, "utf-8");
        const userType = content
            .split("export type User")[1]
            ?.split("export type")[0];

        expect(userType).toBeDefined();
        expect(userType).not.toMatch(/\bpassword\b/);
        expect(userType).not.toMatch(/\bremember_token\b/);
    });

    test.skip("User model types directory exists", () => {
        const userTypesPath = join(
            __dirname,
            "../workbench/resources/js/wayfinder/types/App/Models/User"
        );
        expect(existsSync(userTypesPath)).toBe(true);
    });
});
