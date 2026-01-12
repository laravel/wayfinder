import { expect, it } from "vitest";
import PrismNamespace from "../workbench/resources/js/wayfinder/App/Http/Controllers/Prism";
import Prism from "../workbench/resources/js/wayfinder/App/Http/Controllers/Prism/Prism";

it("avoids barrel export name collision when namespace segment repeats", () => {
    // Parent namespace exports as PrismNamespace to avoid collision with child Prism import
    expect(PrismNamespace.PrismController.index.url()).toBe("/prism");
    expect(PrismNamespace.Prism.PrismController.nested.url()).toBe(
        "/prism/nested",
    );

    // Nested Prism namespace exports normally since it has no child Prism
    expect(Prism.PrismController.nested.url()).toBe("/prism/nested");
});
