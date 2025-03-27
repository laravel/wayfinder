import { expect, test } from "vitest";
import { dynamicDomain, fixedDomain } from "../workbench/resources/js/actions/App/Http/Controllers/DomainController";

test('can generate fixed domain urls', () => {
    expect(fixedDomain.url({ param: 'foo' })).toBe('//example.test/fixed-domain/foo')
})

test('can generate dynamic domain urls', () => {
    expect(dynamicDomain.url({
        domain: 'tim.macdonald',
        param: 'foo',
    })).toBe('//tim.macdonald.au/dynamic-domain/foo')
})
