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

const InertiaController = await import(
    join(wayfinderDir, "App/Http/Controllers/InertiaController.ts")
) as typeof import("../workbench/resources/js/wayfinder/App/Http/Controllers/InertiaController");

const PostController = await import(
    join(wayfinderDir, "App/Http/Controllers/PostController.ts")
) as typeof import("../workbench/resources/js/wayfinder/App/Http/Controllers/PostController");

const namedRoutes = await import(
    join(wayfinderDir, "routes/inertia/index.ts")
) as typeof import("../workbench/resources/js/wayfinder/routes/inertia/index");

describe.skipIf(!withComponent)("Inertia component enabled", () => {
    test("inertia routes include component in base function", () => {
        expect(InertiaController.dashboard()).toMatchObject({ component: "Dashboard" });
        expect(InertiaController.settings()).toMatchObject({ component: "Settings/General" });
        expect(InertiaController.profile()).toMatchObject({ component: "Profile/Show" });
        expect(InertiaController.unsafe()).toMatchObject({ component: "settings/two-factor" });
    });

    test("component is included in definition", () => {
        expect(InertiaController.dashboard.definition).toMatchObject({ component: "Dashboard" });
        expect(InertiaController.settings.definition).toMatchObject({ component: "Settings/General" });
    });

    test("non-inertia routes do not have component", () => {
        expect(PostController.index()).not.toHaveProperty("component");
        expect(PostController.index.definition).not.toHaveProperty("component");
    });

    test("form variants include component", () => {
        expect(InertiaController.dashboard.form()).toMatchObject({ component: "Dashboard" });
        expect(InertiaController.settings.form()).toMatchObject({ component: "Settings/General" });
        expect(InertiaController.profile.form()).toMatchObject({ component: "Profile/Show" });
        expect(InertiaController.unsafe.form()).toMatchObject({ component: "settings/two-factor" });
    });

    test("named routes include component", () => {
        expect(namedRoutes.dashboard()).toMatchObject({ component: "Dashboard" });
        expect(namedRoutes.settings()).toMatchObject({ component: "Settings/General" });
        expect(namedRoutes.profile()).toMatchObject({ component: "Profile/Show" });
    });

    test("conditional routes produce component object", () => {
        const expectedComponent = {
            "Conditional/Authenticated": "Conditional/Authenticated",
            "Conditional/Guest": "Conditional/Guest",
        };

        expect(InertiaController.conditional()).toMatchObject({ component: expectedComponent });
        expect(InertiaController.conditional.definition).toMatchObject({ component: expectedComponent });
    });
});
