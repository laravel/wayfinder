import { expect, it } from "vitest";
import InvokableController from "../workbench/resources/js/actions/App/Http/Controllers/InvokableController";

it('exports default for invokable controllers', () => {
    expect(InvokableController.url()).toBe('/invokable-controller')
    expect(InvokableController()).toEqual({
        uri: '/invokable-controller',
        method: 'get',
    })
})
