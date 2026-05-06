import { readFileSync } from "fs";
import { join } from "path";
import { describe, expect, test } from "vitest";

describe("ResourceData", () => {
    const typesPath = join(
        __dirname,
        "../workbench/resources/js/wayfinder/types.d.ts"
    );

    const types = () => readFileSync(typesPath, "utf-8");

    test("types.d.ts contains ResourceTestController namespace", () => {
        expect(types()).toContain("export namespace ResourceTestController");
    });

    test("single JsonResource emits a `data`-wrapped shape", () => {
        expect(types()).toContain(
            "{ data: { id: number, name: string, price: number, in_stock: true } }"
        );
    });

    test("JsonResource collection emits an array under `data`", () => {
        expect(types()).toContain(
            "{ data: { id: number, name: string, price: number, in_stock: true }[] }"
        );
    });

    test("custom $wrap key is honored", () => {
        expect(types()).toContain(
            "{ product: { id: number, name: string } }"
        );
    });

    test("JsonApiResource emits id/type/attributes/links/meta", () => {
        expect(types()).toContain(
            "{ data: { id: string, type: string, attributes?: { name: string, slug: string, created_at: string }, links?: { self: string }, meta?: { count: number } } }"
        );
    });

    test("JsonApiResource collection wraps the resource shape in an array", () => {
        expect(types()).toContain(
            "{ data: { id: string, type: string, attributes?: { name: string, slug: string, created_at: string }, links?: { self: string }, meta?: { count: number } }[] }"
        );
    });

    test("Eloquent-bound resource resolves $this->property to model types", () => {
        expect(types()).toContain(
            "{ data: { id: number, name: string, email: string } }"
        );
    });

    test("Eloquent-bound resource collection resolves model types", () => {
        expect(types()).toContain(
            "{ data: { id: number, name: string, email: string }[] }"
        );
    });

    test("JsonApiResource relationships emit cardinality-aware shapes", () => {
        // to-one is { data: { id, type } | null }, to-many is { data: { id, type }[] }
        expect(types()).toContain(
            "relationships?: { featuredProduct: {data: {id: string, type: string } | null }, relatedProducts: {data: {id: string, type: string }[] } }"
        );
    });
});
