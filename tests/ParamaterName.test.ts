import { describe, expect, test } from "vitest";
import {
    camel,
    screamingSnake,
    snake,
    studly,
} from "../workbench/resources/js/actions/App/Http/Controllers/ParameterNameController";

describe("camel", () => {
    test("url", () => {
        expect(camel.url({ camelCase: "casing" })).toBe(
            "/parameter-names/casing/camel",
        );
    });

    test("definition", () => {
        expect(camel.definition.url).toBe("/parameter-names/{camelCase}/camel");
    });
});

describe("studly", () => {
    test("url", () => {
        expect(studly.url({ StudlyCase: "casing" })).toBe(
            "/parameter-names/casing/studly",
        );
    });

    test("definition", () => {
        expect(studly.definition.url).toBe(
            "/parameter-names/{StudlyCase}/studly",
        );
    });
});

describe("snake", () => {
    test("url", () => {
        expect(snake.url({ snake_case: "casing" })).toBe(
            "/parameter-names/casing/snake",
        );
    });

    test("definition", () => {
        expect(snake.definition.url).toBe(
            "/parameter-names/{snake_case}/snake",
        );
    });
});

describe("screaming snake", () => {
    test("url", () => {
        expect(screamingSnake.url({ SCREAMING_SNAKE_CASE: "casing" })).toBe(
            "/parameter-names/casing/screaming-snake",
        );
    });

    test("definition", () => {
        expect(screamingSnake.definition.url).toBe(
            "/parameter-names/{SCREAMING_SNAKE_CASE}/screaming-snake",
        );
    });
});
