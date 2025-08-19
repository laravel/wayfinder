import { expect, test } from "vitest";
import {
    defaultParametersDomain,
    fixedDomain,
} from "../workbench/resources/js/actions/App/Http/Controllers/DomainController";

test("can generate fixed domain urls", () => {
    expect(fixedDomain.url({ param: "foo" })).toBe(
        "//example.test/fixed-domain/foo",
    );
});

test("can generate dynamic domain urls", () => {
    expect(
        defaultParametersDomain.url({
            defaultDomain: "tim.macdonald",
            param: "foo",
        }),
    ).toBe("//tim.macdonald.au/default-parameters-domain/foo");
});
