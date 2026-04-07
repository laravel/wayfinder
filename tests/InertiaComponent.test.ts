import { describe, expect, expectTypeOf, test } from "vitest";
import InertiaController from "../workbench/resources/js/wayfinder/App/Http/Controllers/InertiaController";
import PostController from "../workbench/resources/js/wayfinder/App/Http/Controllers/PostController";
import namedRoutes from "../workbench/resources/js/wayfinder/routes/inertia/index";

const withComponent = !!process.env.WAYFINDER_GENERATE_INERTIA_COMPONENT;

describe.skipIf(withComponent)("Inertia component disabled", () => {
    test("named routes do not include component", () => {
        expect(namedRoutes.dashboard()).not.toHaveProperty("component");
        expect(namedRoutes.settings()).not.toHaveProperty("component");
        expect(namedRoutes.profile()).not.toHaveProperty("component");
        expect(namedRoutes.unsafe()).not.toHaveProperty("component");
        expect(namedRoutes.conditional()).not.toHaveProperty("component");
    });

    test("inertia routes do not include component in base function", () => {
        expect(InertiaController.dashboard()).not.toHaveProperty("component");
        expect(InertiaController.settings()).not.toHaveProperty("component");
    });
});

describe.skipIf(!withComponent)("Inertia component enabled", () => {
    test("inertia routes include component in base function", () => {
        expect(InertiaController.dashboard()).not.toHaveProperty("component");
        expect(InertiaController.dashboard.withComponent()).toMatchObject({
            component: "Dashboard",
        });
        expect(InertiaController.settings()).not.toHaveProperty("component");
        expect(InertiaController.settings.withComponent()).toMatchObject({
            component: "Settings/General",
        });
        expect(InertiaController.profile()).not.toHaveProperty("component");
        expect(InertiaController.profile.withComponent()).toMatchObject({
            component: "Profile/Show",
        });
        expect(InertiaController.unsafe()).not.toHaveProperty("component");
        expect(InertiaController.unsafe.withComponent()).toMatchObject({
            component: "settings/two-factor",
        });
    });

    test("component is included in definition", () => {
        expect(InertiaController.dashboard.definition).toMatchObject({
            component: "Dashboard",
        });
        expect(InertiaController.settings.definition).toMatchObject({
            component: "Settings/General",
        });
    });

    test("non-inertia routes do not have component", () => {
        expect(PostController.index()).not.toHaveProperty("component");
        expect(PostController.index.definition).not.toHaveProperty("component");
    });

    test("form variants include component", () => {
        expect(InertiaController.dashboard.form.withComponent()).toMatchObject({
            component: "Dashboard",
        });
        expect(InertiaController.settings.form.withComponent()).toMatchObject({
            component: "Settings/General",
        });
        expect(InertiaController.profile.form.withComponent()).toMatchObject({
            component: "Profile/Show",
        });
        expect(InertiaController.unsafe.form.withComponent()).toMatchObject({
            component: "settings/two-factor",
        });
    });

    test("named routes include component", () => {
        expect(namedRoutes.dashboard.withComponent()).toMatchObject({
            component: "Dashboard",
        });
        expect(namedRoutes.settings.withComponent()).toMatchObject({
            component: "Settings/General",
        });
        expect(namedRoutes.profile.withComponent()).toMatchObject({
            component: "Profile/Show",
        });
    });

    test("conditional routes produce component object", () => {
        const expectedComponent = {
            "Conditional/Authenticated": "Conditional/Authenticated",
            "Conditional/Guest": "Conditional/Guest",
        };

        expect(
            InertiaController.conditional.withComponent(
                "Conditional/Authenticated",
            ),
        ).toMatchObject({
            component: "Conditional/Authenticated",
        });

        expect(
            InertiaController.conditional.withComponent("Conditional/Guest"),
        ).toMatchObject({
            component: "Conditional/Guest",
        });

        expect(InertiaController.conditional.definition).toMatchObject({
            component: expectedComponent,
        });
    });

    test("base route and form types are compatible with component?: string", () => {
        expectTypeOf(InertiaController.dashboard()).toMatchTypeOf<{ component?: string }>();
        expectTypeOf(InertiaController.dashboard.form()).toMatchTypeOf<{ component?: string }>();
    });

    test("withComponent return type has string component", () => {
        expectTypeOf(InertiaController.dashboard.withComponent()).toMatchTypeOf<{ component: string }>();
        expectTypeOf(InertiaController.dashboard.form.withComponent()).toMatchTypeOf<{ component: string }>();
        expectTypeOf(InertiaController.conditional.withComponent("Conditional/Authenticated")).toMatchTypeOf<{ component: string }>();
        expectTypeOf(InertiaController.conditional.form.withComponent("Conditional/Authenticated")).toMatchTypeOf<{ component: string }>();
    });

    test("single-component definition has string component", () => {
        expectTypeOf(InertiaController.dashboard.definition).toMatchTypeOf<{ component: string }>();
    });

    test("multi-component definition has Record component", () => {
        expectTypeOf(InertiaController.conditional.definition).toMatchTypeOf<{ component: Record<string, string> }>();
    });
});
