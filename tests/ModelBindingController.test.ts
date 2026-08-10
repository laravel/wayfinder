import { readFileSync } from "fs";
import { join } from "path";
import { expect, test } from "vitest";
import {
    nullableBinding,
    show,
} from "../workbench/resources/js/wayfinder/App/Http/Controllers/ModelBindingController";

test("will detect model binding", () => {
    expect(show.url(1)).toBe("/users/1");
    expect(show.url({ user: 1 })).toBe("/users/1");
    expect(show.url([1])).toBe("/users/1");
});

test("will generate urls for a nullable binding column", () => {
    const token = "547a7452-9dc5-4f64-a275-d646dea6ebcf";

    expect(nullableBinding.url(token)).toBe(`/users/by-token/${token}`);
    expect(nullableBinding.url({ user: token })).toBe(
        `/users/by-token/${token}`,
    );
    expect(nullableBinding.url([token])).toBe(`/users/by-token/${token}`);
    expect(nullableBinding.url({ remember_token: token })).toBe(
        `/users/by-token/${token}`,
    );
});

test("will throw when a nullable binding column is null", () => {
    expect(() => nullableBinding.url({ remember_token: null })).toThrowError(
        'Parameter "user" is null. Unable to generate a URL.',
    );

    expect(() =>
        nullableBinding.url({ user: { remember_token: null } }),
    ).toThrowError('Parameter "user" is null. Unable to generate a URL.');

    expect(() =>
        nullableBinding.url([{ remember_token: null }]),
    ).toThrowError('Parameter "user" is null. Unable to generate a URL.');
});

test("will accept an object with a nullable binding column, but not a bare null", () => {
    const source = readFileSync(
        join(
            __dirname,
            "../workbench/resources/js/wayfinder/App/Http/Controllers/ModelBindingController.ts",
        ),
        "utf-8",
    );

    expect(source).toContain(
        "args: { user: string | { remember_token: string | null } }",
    );
    expect(source).not.toContain("user: string | null");
});
