import { readFileSync } from "fs";
import { join } from "path";
import { describe, expect, test } from "vitest";

describe("Modular Inertia Components (:: separator)", () => {
    const typesPath = join(
        __dirname,
        "../workbench/resources/js/wayfinder/types.d.ts",
    );
    const content = readFileSync(typesPath, "utf-8");

    test("does not contain double-colon in type names", () => {
        const lines = content.split("\n");
        const invalidLines = lines.filter(
            (line) =>
                (line.includes("export type") || line.includes("export namespace")) &&
                line.includes("::"),
        );

        expect(invalidLines).toEqual([]);
    });

    test("generates Authorization namespace under Pages", () => {
        const pagesSection = content.match(
            /export namespace Pages\s*\{[\s\S]*?\n    \}/,
        );

        expect(pagesSection).toBeTruthy();
        expect(pagesSection?.[0]).toContain("export namespace Authorization");
    });

    test("generates Login type inside Authorization namespace", () => {
        expect(content).toMatch(/export type Login\s*=/);
    });

    test("generates Register type inside Authorization namespace", () => {
        expect(content).toMatch(/export type Register\s*=/);
    });

    test("controller response types reference nested namespace path", () => {
        expect(content).toContain("Inertia.Pages.Authorization.Login");
        expect(content).toContain("Inertia.Pages.Authorization.Register");
    });
});
