import { describe, expect, test } from "vitest";
import { readFileSync } from "fs";
import { join } from "path";

describe("EnvironmentVariables", () => {
    const viteEnvPath = join(
        __dirname,
        "../workbench/resources/js/wayfinder/vite-env.d.ts"
    );

    test("generates vite-env.d.ts file", () => {
        const content = readFileSync(viteEnvPath, "utf-8");
        expect(content).toBeDefined();
        expect(content.length).toBeGreaterThan(0);
    });

    test("contains vite client reference", () => {
        const content = readFileSync(viteEnvPath, "utf-8");
        expect(content).toContain('/// <reference types="vite/client" />');
    });

    test("defines ImportMetaEnv interface", () => {
        const content = readFileSync(viteEnvPath, "utf-8");
        expect(content).toContain("interface ImportMetaEnv");
    });

    test("defines ImportMeta interface", () => {
        const content = readFileSync(viteEnvPath, "utf-8");
        expect(content).toContain("interface ImportMeta");
        expect(content).toContain("readonly env: ImportMetaEnv");
    });

    test("includes VITE_ prefixed environment variables", () => {
        const content = readFileSync(viteEnvPath, "utf-8");
        expect(content).toContain("VITE_APP_NAME");
    });

    test("types every value as a string, as Vite exposes them", () => {
        const content = readFileSync(viteEnvPath, "utf-8");
        for (const key of [
            "VITE_APP_NAME",
            "VITE_DEV_PORT",
            "VITE_SAMPLE_RATE",
            "VITE_FEATURE_FLAG",
            "VITE_EMPTY",
        ]) {
            expect(content).toContain(`readonly ${key}: string`);
        }
        expect(content).not.toMatch(/readonly VITE_\w+: (number|boolean|null)/);
    });
});
