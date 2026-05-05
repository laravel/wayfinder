import { readFileSync } from "fs";
import { join } from "path";
import { describe, expect, test } from "vitest";

describe("EloquentProductController", () => {
    const typesPath = join(
        __dirname,
        "../workbench/resources/js/wayfinder/types.d.ts",
    );

    test("types.d.ts contains EloquentProducts namespace under Inertia.Pages", () => {
        const content = readFileSync(typesPath, "utf-8");
        expect(content).toContain("export namespace EloquentProducts");
    });

    test("Inertia.Pages.EloquentProducts.Index type is generated", () => {
        const content = readFileSync(typesPath, "utf-8");
        expect(content).toContain("Inertia.Pages.EloquentProducts.Index");
    });

    test("Inertia.Pages.EloquentProducts.Index contains products: App.Models.Product[]", () => {
        const content = readFileSync(typesPath, "utf-8");
        expect(content).toContain("products: App.Models.Product[]");
    });

    test("Inertia.Pages.EloquentProducts.Index does not contain products: Illuminate.Database.Eloquent.Collection", () => {
        const content = readFileSync(typesPath, "utf-8");
        expect(content).not.toContain(
            "products: Illuminate.Database.Eloquent.Collection",
        );
    });

    test("Inertia.Pages.EloquentProducts.Show type is generated", () => {
        const content = readFileSync(typesPath, "utf-8");
        expect(content).toContain("Inertia.Pages.EloquentProducts.Show");
    });

    test("Inertia.Pages.EloquentProducts.Show contains product: App.Models.Product", () => {
        const content = readFileSync(typesPath, "utf-8");
        expect(content).toContain("product: App.Models.Product");
    });
});
