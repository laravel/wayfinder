import { existsSync, readFileSync } from "fs";
import { join } from "path";
import { describe, expect, test } from "vitest";

describe("EmptyCollections", () => {
    const wayfinderDir = join(__dirname, "../workbench/resources/js/wayfinder");

    test("build succeeds even when converters have data", () => {
        // The build process (globalSetup in vite.config.js) runs before tests
        // If we get here, the build succeeded
        expect(existsSync(wayfinderDir)).toBe(true);
    });

    test("index.ts is always generated", () => {
        const indexPath = join(wayfinderDir, "index.ts");
        expect(existsSync(indexPath)).toBe(true);
    });

    test("types.d.ts is generated when there are types", () => {
        const typesPath = join(wayfinderDir, "types.d.ts");
        expect(existsSync(typesPath)).toBe(true);

        const content = readFileSync(typesPath, "utf-8");
        expect(content.length).toBeGreaterThan(0);
    });

    test("broadcast-channels.ts is generated when channels exist", () => {
        // Since we added channels.php, this file should exist
        const channelsPath = join(wayfinderDir, "broadcast-channels.ts");
        expect(existsSync(channelsPath)).toBe(true);
    });

    test("broadcast-events.ts is generated when events exist", () => {
        // Since we added broadcast events, this file should exist
        const eventsPath = join(wayfinderDir, "broadcast-events.ts");
        expect(existsSync(eventsPath)).toBe(true);
    });

    test("vite-env.d.ts is generated when VITE_ env vars exist", () => {
        const viteEnvPath = join(wayfinderDir, "vite-env.d.ts");
        expect(existsSync(viteEnvPath)).toBe(true);
    });

    test("routes directory is generated when named routes exist", () => {
        const routesDir = join(wayfinderDir, "routes");
        expect(existsSync(routesDir)).toBe(true);
    });

    test.skip("types directory is generated when types are defined", () => {
        const typesDir = join(wayfinderDir, "types");
        expect(existsSync(typesDir)).toBe(true);
    });
});
