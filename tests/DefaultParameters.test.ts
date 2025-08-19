import { expect, test } from "vitest";
import {
    defaultParametersDomain,
    fixedDomain,
} from "../workbench/resources/js/actions/App/Http/Controllers/DomainController";
import { setUrlDefaults } from "../workbench/resources/js/wayfinder";

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
