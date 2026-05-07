import { readFileSync } from "fs";
import { join } from "path";
import { describe, expect, test } from "vitest";

describe("InertiaData with API Resources", () => {
    const typesPath = join(
        __dirname,
        "../workbench/resources/js/wayfinder/types.d.ts"
    );

    test("a UserResource::collection() in Inertia data generates a typed array", () => {
        const content = readFileSync(typesPath, "utf-8");
        expect(content).toContain(
            "export type UsersList = Inertia.SharedData & { users: { id: number, name: string, email: string }[], featured: { id: number, name: string, email: string } }"
        );
    });

    test("does not leak the AnonymousResourceCollection class name", () => {
        const content = readFileSync(typesPath, "utf-8");
        expect(content).not.toContain("AnonymousResourceCollection");
    });
});
