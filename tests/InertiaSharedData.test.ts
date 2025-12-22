import { describe, expect, test } from "vitest";
import { readFileSync } from "fs";
import { join } from "path";

describe("InertiaSharedData", () => {
    const typesPath = join(
        __dirname,
        "../workbench/resources/js/wayfinder/types.d.ts"
    );

    test("types.d.ts contains Inertia namespace", () => {
        const content = readFileSync(typesPath, "utf-8");
        expect(content).toContain("export namespace Inertia");
    });

    test("types.d.ts contains SharedData type", () => {
        const content = readFileSync(typesPath, "utf-8");
        expect(content).toContain("SharedData");
    });
});
