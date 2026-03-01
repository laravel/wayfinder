import { readFileSync } from "fs";
import { join } from "path";
import { describe, expect, test } from "vitest";

const wayfinderDir = join(__dirname, "../workbench/resources/js/wayfinder");

const withComponent = !!process.env.WAYFINDER_GENERATE_INERTIA_COMPONENT;

describe.skipIf(withComponent)("Inertia component disabled", () => {
    const inertiaController = readFileSync(
        join(wayfinderDir, "App/Http/Controllers/InertiaController.ts"),
        "utf-8",
    );
    const namedRoutes = readFileSync(
        join(wayfinderDir, "routes/inertia/index.ts"),
        "utf-8",
    );

    test("controller actions do not include component", () => {
        expect(inertiaController).not.toContain("component:");
    });

    test("named routes do not include component", () => {
        expect(namedRoutes).not.toContain("component:");
    });
});

describe.skipIf(!withComponent)("Inertia component enabled", () => {
    const inertiaController = readFileSync(
        join(wayfinderDir, "App/Http/Controllers/InertiaController.ts"),
        "utf-8",
    );
    const postController = readFileSync(
        join(wayfinderDir, "App/Http/Controllers/PostController.ts"),
        "utf-8",
    );
    const namedRoutes = readFileSync(
        join(wayfinderDir, "routes/inertia/index.ts"),
        "utf-8",
    );

    test("inertia routes include component in base function", () => {
        expect(inertiaController).toContain('component: "Dashboard"');
        expect(inertiaController).toContain('component: "Settings/General"');
        expect(inertiaController).toContain('component: "Profile/Show"');
        expect(inertiaController).toContain('component: "settings/two-factor"');
    });

    test("component is included in definition", () => {
        const dashboardDef = inertiaController.match(
            /dashboard\.definition\s*=\s*\{[^}]+\}/s,
        )?.[0];
        expect(dashboardDef).toContain('component: "Dashboard"');

        const settingsDef = inertiaController.match(
            /settings\.definition\s*=\s*\{[^}]+\}/s,
        )?.[0];
        expect(settingsDef).toContain('component: "Settings/General"');
    });

    test("non-inertia routes do not have component", () => {
        expect(postController).not.toContain("component:");
    });

    test("form variants include component", () => {
        const formBlocks = inertiaController.match(
            /const \w+Form = .+?(?=\n\n)/gs,
        );
        expect(formBlocks).toBeTruthy();

        for (const block of formBlocks!) {
            expect(block).toContain("component:");
        }
    });

    test("named routes include component", () => {
        expect(namedRoutes).toContain('component: "Dashboard"');
        expect(namedRoutes).toContain('component: "Settings/General"');
        expect(namedRoutes).toContain('component: "Profile/Show"');
    });

    test("conditional routes produce component array", () => {
        expect(inertiaController).toContain(
            'component: ["Conditional/Authenticated", "Conditional/Guest"]',
        );

        const conditionalDef = inertiaController.match(
            /conditional\.definition\s*=\s*\{[^}]+\}/s,
        )?.[0];
        expect(conditionalDef).toContain(
            'component: ["Conditional/Authenticated", "Conditional/Guest"]',
        );
    });
});
