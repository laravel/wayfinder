import { describe, expect, expectTypeOf, test } from "vitest";
import {
    PostStatus,
    Draft,
    Published,
    Archived,
} from "../workbench/resources/js/wayfinder/App/Enums/PostStatus";
import { UnitEnum } from "../workbench/resources/js/wayfinder/App/Enums/UnitEnum";
import type { App } from "../workbench/resources/js/wayfinder/types";
import {
    ProductStatus,
    used,
    Active,
} from "../workbench/resources/js/wayfinder/App/Enums/ProductStatus";
import {
    EscapedStatus,
    Quote,
    StartsWithQuote,
    Backtick,
    Backslash,
    Newline, LiteralBackslash, LiteralBacktick,
} from "../workbench/resources/js/wayfinder/App/Enums/EscapedStatus";

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

    test("namespaced unit enum type is a numeric union", () => {
        expectTypeOf<App.Enums.UnitEnum>().toEqualTypeOf<0 | 1 | 2>();
    });

    test("namespaced string-backed enum type is a string union", () => {
        expectTypeOf<App.Enums.PostStatus>().toEqualTypeOf<
            "draft" | "published" | "archived"
        >();
    });

    test("namespaced escaped string-backed enum type is a string union", () => {
        expectTypeOf<App.Enums.EscapedStatus>().toEqualTypeOf<
            | "\\"
            | "`"
            | 'they said "yes"'
            | '"quoted"'
            | "`template`"
            | "path\\to\\file"
            | "line\nbreak"
        >();
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

    test("has correct enum cases requiring escaping", () => {
        expect(EscapedStatus.LiteralBackslash).toBe("\\");
        expect(EscapedStatus.LiteralBacktick).toBe("`");
        expect(EscapedStatus.Quote).toBe('they said "yes"');
        expect(EscapedStatus.StartsWithQuote).toBe('"quoted"');
        expect(EscapedStatus.Backtick).toBe("`template`");
        expect(EscapedStatus.Backslash).toBe("path\\to\\file");
        expect(EscapedStatus.Newline).toBe("line\nbreak");
    });

    test("exposes all keys for enums requiring escaping", () => {
        expect(Object.keys(EscapedStatus)).toEqual([
            "LiteralBackslash",
            "LiteralBacktick",
            "Quote",
            "StartsWithQuote",
            "Backtick",
            "Backslash",
            "Newline",
        ]);
    });

    test("exports individual escaped case constants", () => {
        expect(LiteralBackslash).toBe("\\");
        expect(LiteralBacktick).toBe("`");
        expect(Quote).toBe('they said "yes"');
        expect(StartsWithQuote).toBe('"quoted"');
        expect(Backtick).toBe("`template`");
        expect(Backslash).toBe("path\\to\\file");
        expect(Newline).toBe("line\nbreak");
    });
});
