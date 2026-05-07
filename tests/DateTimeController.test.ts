import { readFileSync } from "fs";
import { join } from "path";
import { describe, expect, test } from "vitest";

describe("DateTimeController", () => {
    const typesPath = join(
        __dirname,
        "../workbench/resources/js/wayfinder/types.d.ts",
    );

    const types = () => readFileSync(typesPath, "utf-8");

    test("native DateTimeImmutable and DateTime values are typed as string", () => {
        expect(types()).toContain("{ immutable: string, mutable: string }");
        expect(types()).not.toContain("immutable: DateTimeImmutable");
        expect(types()).not.toContain("mutable: DateTime");
    });
});
