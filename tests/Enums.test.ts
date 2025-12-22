import { describe, expect, test } from "vitest";
import {
    PostStatus,
    Draft,
    Published,
    Archived,
} from "../workbench/resources/js/wayfinder/App/Enums/PostStatus";

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

    test("exports individual case constants", () => {
        expect(Draft).toBe("draft");
        expect(Published).toBe("published");
        expect(Archived).toBe("archived");
    });
});
