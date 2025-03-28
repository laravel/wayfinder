import { describe, expect, test } from "vitest";
import {
    mixedDefaults,
    onlyDefaults,
} from "../workbench/resources/js/actions/App/Http/Controllers/UrlDefaultsController";

describe("onlyDefault", async () => {
    test("url", () => {
        expect(onlyDefaults.url()).toBe("/with-defaults/en");
        expect(onlyDefaults.url({ locale: "es" })).toBe("/with-defaults/es");
    });

    test("default method", () => {
        expect(onlyDefaults()).toEqual({
            url: "/with-defaults/en",
            method: "post",
        });

        expect(onlyDefaults({ locale: "es" })).toEqual({
            url: "/with-defaults/es",
            method: "post",
        });
    });
});

describe("mixedDefault", async () => {
    test("url", () => {
        expect(mixedDefaults.url({ timezone: "UTC" })).toBe(
            "/with-defaults/en/also/UTC",
        );
        expect(mixedDefaults.url({ timezone: "UTC", locale: "es" })).toBe(
            "/with-defaults/es/also/UTC",
        );
    });

    test("default method", () => {
        expect(mixedDefaults({ timezone: "UTC" })).toEqual({
            url: "/with-defaults/en/also/UTC",
            method: "post",
        });

        expect(mixedDefaults({ timezone: "UTC", locale: "es" })).toEqual({
            url: "/with-defaults/es/also/UTC",
            method: "post",
        });
    });
});
