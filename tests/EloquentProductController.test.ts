import { readFileSync } from "fs";
import { join } from "path";
import { describe, expect, test } from "vitest";

describe("EloquentProductController", () => {
    const typesPath = join(
        __dirname,
        "../workbench/resources/js/wayfinder/types.d.ts",
    );

    const types = () => readFileSync(typesPath, "utf-8");

    test("types.d.ts contains EloquentProducts namespace under Inertia.Pages", () => {
        expect(types()).toContain("export namespace EloquentProducts");
    });

    test("Inertia.Pages.EloquentProducts.Index type is generated", () => {
        expect(types()).toContain("Inertia.Pages.EloquentProducts.Index");
    });

    test("Inertia.Pages.EloquentProducts.Index contains products: App.Models.Product[]", () => {
        expect(types()).toContain("{ products: App.Models.Product[] }");
    });

    test("Inertia.Pages.EloquentProducts.Index does not contain products: Illuminate.Database.Eloquent.Collection", () => {
        expect(types()).not.toContain(
            "products: Illuminate.Database.Eloquent.Collection",
        );
    });

    test("Inertia.Pages.EloquentProducts.Show type is generated", () => {
        expect(types()).toContain("Inertia.Pages.EloquentProducts.Show");
    });

    test("Inertia.Pages.EloquentProducts.Show contains product: App.Models.Product", () => {
        expect(types()).toContain("{ product: App.Models.Product }");
    });
});
