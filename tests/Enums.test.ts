import { describe, expect, test } from "vitest";
import {
    PostStatus,
    Draft,
    Published,
    Archived,
} from "../workbench/resources/js/wayfinder/App/Enums/PostStatus";
import { UnitEnum } from "../workbench/resources/js/wayfinder/App/Enums/UnitEnum";
import {
    ProductStatus,
    used,
    Active,
} from "../workbench/resources/js/wayfinder/App/Enums/ProductStatus";

describe("Enums", () => {
    test("exports enum constant object", () => {
        expect(PostStatus).toBeDefined();
        expect(typeof PostStatus).toBe("object");
    });

    test("has correct enum cases", () => {
        expect(PostStatus.Draft).toBe("draft");
        expect(PostStatus.Published).toBe("published");
        expect(PostStatus.Archived).toBe("archived");
    });

    test("has only expected keys", () => {
        expect(Object.keys(PostStatus)).toEqual([
            "Draft",
            "Published",
            "Archived",
        ]);
    });

    test("has correct enum cases with numeric values", () => {
        expect(UnitEnum.None).toBe(0);
        expect(UnitEnum.Open).toBe(1);
        expect(UnitEnum.Done).toBe(2);
    });

    test("has only expected keys", () => {
        expect(Object.keys(UnitEnum)).toEqual(["None", "Open", "Done"]);
    });

    test("exports individual case constants", () => {
        expect(UnitEnum.None).toBe(0);
        expect(UnitEnum.Open).toBe(1);
        expect(UnitEnum.Done).toBe(2);
    });

    test("exports individual case constants", () => {
        expect(Draft).toBe("draft");
        expect(Published).toBe("published");
        expect(Archived).toBe("archived");
    });

    test("handles reserved keyword case names", () => {
        expect(ProductStatus.new).toBe("new");
        expect(ProductStatus.used).toBe("used");
        expect(ProductStatus.for).toBe("for-sale");
        expect(ProductStatus.Active).toBe("active");
    });

    test("exposes all keys for enums with reserved keyword cases", () => {
        expect(Object.keys(ProductStatus)).toEqual([
            "new",
            "used",
            "for",
            "Active",
        ]);
    });

    test("only exports non-reserved case constants individually", () => {
        expect(used).toBe("used");
        expect(Active).toBe("active");
    });
});
