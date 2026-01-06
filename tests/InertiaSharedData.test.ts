import { readFileSync } from "fs";
import { join } from "path";
import { describe, expect, test } from "vitest";

describe("InertiaSharedData", () => {
    const typesPath = join(
        __dirname,
        "../workbench/resources/js/wayfinder/types.d.ts",
    );

    const inertiaConfigPath = join(
        __dirname,
        "../workbench/resources/js/wayfinder/inertia-config.d.ts",
    );

    test("types.d.ts contains Inertia namespace", () => {
        const content = readFileSync(typesPath, "utf-8");
        expect(content).toContain("export namespace Inertia");
    });

    test("types.d.ts contains SharedData type", () => {
        const content = readFileSync(typesPath, "utf-8");
        expect(content).toContain("SharedData");
    });

    test("inertia-config.d.ts declares module for @inertiajs/core", () => {
        const content = readFileSync(inertiaConfigPath, "utf-8");
        expect(content).toContain("declare module '@inertiajs/core'");
    });

    test("inertia-config.d.ts exports InertiaConfig interface", () => {
        const content = readFileSync(inertiaConfigPath, "utf-8");
        expect(content).toContain("export interface InertiaConfig");
    });

    test("inertia-config.d.ts contains sharedPageProps", () => {
        const content = readFileSync(inertiaConfigPath, "utf-8");
        expect(content).toContain("sharedPageProps");
    });

    test("inertia-config.d.ts contains errorValueType when withAllErrors is true", () => {
        const content = readFileSync(inertiaConfigPath, "utf-8");
        expect(content).toContain("errorValueType: string[]");
    });
});
