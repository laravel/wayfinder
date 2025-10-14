import { expect, it } from "vitest";
import nested from "../workbench/resources/js/routes/nested";
import Nested from "../workbench/resources/js/actions/App/Http/Controllers/Nested";

it("can handle conflicting nested route names", () => {
    expect(nested.child().url).toBe("/nested/controller/child");
    expect(nested.child.grandchild().url).toBe(
        "/nested/controller/child/grandchild",
    );
    expect(Nested.Nested.nested().url).toBe("/nested");
});
