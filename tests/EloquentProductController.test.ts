import { readFileSync } from "fs";
import { join } from "path";
import { describe, expect, test } from "vitest";

describe("EloquentProductController", () => {
    const typesPath = join(
        __dirname,
        "../workbench/resources/js/wayfinder/types.d.ts",
    );

    const types = () => readFileSync(typesPath, "utf-8");

    test("Index narrows products to App.Models.Product[]", () => {
        expect(types()).toContain("{ products: App.Models.Product[] }");
        expect(types()).not.toContain(
            "products: Illuminate.Database.Eloquent.Collection",
        );
    });

    test("Show narrows product to App.Models.Product", () => {
        expect(types()).toContain("{ product: App.Models.Product }");
    });
});
