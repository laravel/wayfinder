import { expect, test } from "vitest";
import {
    addUrlDefault,
    applyUrlDefaults,
    setUrlDefaults,
    withUrlDefaults,
} from "../workbench/resources/js/wayfinder";
import {
    defaultParametersDomain,
    fixedDomain,
} from "../workbench/resources/js/wayfinder/App/Http/Controllers/DomainController";

test("it can generate urls without default parameters set", () => {
    expect(fixedDomain.url({ param: "foo" })).toBe(
        "//example.test/fixed-domain/foo",
    );
});

test("it can generate urls with default URL parameters set on backend and frontend", () => {
    setUrlDefaults({
        defaultDomain: "tim.macdonald",
    });

    expect(
        defaultParametersDomain.url({
            param: "foo",
        }),
    ).toBe("//tim.macdonald.au/default-parameters-domain/foo");
});

test("it can generate urls with dynamic function-based default URL parameters", () => {
    let callCount = 0;

    setUrlDefaults(() => {
        callCount++;

        return {
            defaultDomain: `dynamic-${callCount}.test`,
        };
    });

    expect(
        defaultParametersDomain.url({
            param: "foo",
        }),
    ).toBe("//dynamic-1.test.au/default-parameters-domain/foo");

    expect(
        defaultParametersDomain.url({
            param: "bar",
        }),
    ).toBe("//dynamic-2.test.au/default-parameters-domain/bar");

    expect(callCount).toBe(2);
});

test("it preserves dynamic URL defaults when adding runtime defaults", () => {
    let callCount = 0;

    setUrlDefaults(() => {
        callCount++;
        return {
            defaultDomain: `dynamic-${callCount}.test`,
        };
    });

    addUrlDefault("locale", "en");

    expect(applyUrlDefaults(undefined)).toEqual({
        defaultDomain: "dynamic-1.test",
        locale: "en",
    });

    expect(applyUrlDefaults(undefined)).toEqual({
        defaultDomain: "dynamic-2.test",
        locale: "en",
    });

    expect(callCount).toBe(2);
});

test("it applies scoped URL defaults for a callback", () => {
    setUrlDefaults({
        defaultDomain: "base.test",
        locale: "en",
    });

    expect(
        withUrlDefaults(
            {
                locale: "fr",
            },
            () => applyUrlDefaults(undefined)
        )
    ).toEqual({
        defaultDomain: "base.test",
        locale: "fr",
    });

    expect(applyUrlDefaults(undefined)).toEqual({
        defaultDomain: "base.test",
        locale: "en",
    });
});

test("it preserves dynamic URL defaults when applying scoped defaults", () => {
    let callCount = 0;

    setUrlDefaults(() => {
        callCount++;

        return {
            defaultDomain: `dynamic-${callCount}.test`,
        };
    });

    expect(
        withUrlDefaults(
            {
                locale: "en",
            },
            () => applyUrlDefaults(undefined)
        )
    ).toEqual({
        defaultDomain: "dynamic-1.test",
        locale: "en",
    });

    expect(applyUrlDefaults(undefined)).toEqual({
        defaultDomain: "dynamic-2.test",
    });

    expect(callCount).toBe(2);
});

test("it restores URL defaults after an async callback settles", async () => {
    setUrlDefaults({
        defaultDomain: "base.test",
    });

    await expect(
        withUrlDefaults(
            {
                defaultDomain: "async.test",
            },
            async () => {
                await Promise.resolve();

                return defaultParametersDomain.url({
                    param: "foo",
                });
            }
        )
    ).resolves.toBe("//async.test.au/default-parameters-domain/foo");

    expect(
        defaultParametersDomain.url({
            param: "foo",
        })
    ).toBe("//base.test.au/default-parameters-domain/foo");
});

test("it restores URL defaults after a callback throws", () => {
    setUrlDefaults({
        defaultDomain: "base.test",
    });

    expect(() =>
        withUrlDefaults(
            {
                defaultDomain: "scoped.test",
            },
            () => {
                throw new Error("Scoped failure");
            }
        )
    ).toThrow("Scoped failure");

    expect(
        defaultParametersDomain.url({
            param: "foo",
        })
    ).toBe("//base.test.au/default-parameters-domain/foo");
});
