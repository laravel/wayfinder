import { expect, test } from "vitest";
import {
    defaultParametersDomain,
    fixedDomain,
} from "../workbench/resources/js/actions/App/Http/Controllers/DomainController";
import {
    addUrlDefault,
    applyUrlDefaults,
    setUrlDefaults,
} from "../workbench/resources/js/wayfinder";

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
