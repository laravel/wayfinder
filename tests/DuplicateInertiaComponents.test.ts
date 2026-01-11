import { readFileSync } from "fs";
import { join } from "path";
import { describe, expect, test } from "vitest";

/**
 * These tests verify the deduplication behavior for Inertia components.
 *
 * When multiple controller methods render the same Inertia component,
 * Wayfinder should generate only ONE TypeScript type definition for that component,
 * while preserving references to all controller methods that use it.
 *
 * This prevents duplicate type definition errors in TypeScript.
 */
describe("Duplicate Inertia Components Deduplication", () => {
    const typesPath = join(
        __dirname,
        "../workbench/resources/js/wayfinder/types.d.ts",
    );
    const content = readFileSync(typesPath, "utf-8");

    test("types.d.ts file is generated", () => {
        expect(content).toBeDefined();
        expect(content.length).toBeGreaterThan(0);
    });

    describe("Dashboard component (rendered by dashboard() and duplicate())", () => {
        test("contains Inertia.Pages namespace", () => {
            expect(content).toContain("export namespace Inertia");
            expect(content).toContain("export namespace Pages");
        });

        test("generates only ONE type definition for Dashboard", () => {
            // Match "export type Dashboard =" anywhere in the file
            const dashboardTypeMatches = content.match(/export type Dashboard\s*=/g);

            expect(dashboardTypeMatches).toBeTruthy();
            expect(dashboardTypeMatches?.length).toBe(1);
        });

        test("Dashboard type has correct definition", () => {
            // The Dashboard type should extend SharedData with stats and recentActivity
            expect(content).toMatch(/export type Dashboard\s*=\s*Inertia\.SharedData\s*&/);
        });
    });

    describe("Settings/General component (rendered by settings() and duplicateWithData())", () => {
        test("generates only ONE type definition for General", () => {
            // Match "export type General =" in Settings namespace
            const generalTypeMatches = content.match(/export type General\s*=/g);

            expect(generalTypeMatches).toBeTruthy();
            expect(generalTypeMatches?.length).toBe(1);
        });

        test("General type has correct definition with props", () => {
            // The General type should extend SharedData and include user and preferences props
            expect(content).toMatch(
                /export type General\s*=\s*Inertia\.SharedData\s*&\s*\{[\s\S]*?user[\s\S]*?\}/
            );
        });

        test("user prop has correct structure", () => {
            // Extract the General type definition and check for user prop
            const generalTypeMatch = content.match(
                /export type General\s*=\s*Inertia\.SharedData\s*&\s*\{[^}]*user:[^}]*\}/
            );

            expect(generalTypeMatch).toBeTruthy();
        });
    });

    describe("No duplicate type definitions in entire file", () => {
        test("Dashboard type appears only once", () => {
            const dashboardCount = (
                content.match(/export type Dashboard\s*=/g) || []
            ).length;

            expect(dashboardCount).toBe(1);
        });

        test("General type appears only once", () => {
            const generalCount = (
                content.match(/export type General\s*=/g) || []
            ).length;

            expect(generalCount).toBe(1);
        });

        test("both controller methods are referenced in JSDoc", () => {
            // The JSDoc should reference both InertiaController::settings and DuplicateInertiaController::duplicateWithData
            const generalTypeSection = content.match(
                /\/\*\*[\s\S]*?export type General\s*=/
            );

            expect(generalTypeSection).toBeTruthy();

            const jsdocComment = generalTypeSection?.[0] || "";

            // Both controllers should be referenced
            expect(jsdocComment).toContain("InertiaController");
            expect(jsdocComment).toContain("DuplicateInertiaController");
        });
    });
});
