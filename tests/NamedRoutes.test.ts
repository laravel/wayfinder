import { expect, it } from "vitest";
import { edit } from "../workbench/resources/js/named/posts";

it('exports default and methods for invokable controllers', () => {
    expect(edit.url(1)).toBe('/posts/1/edit')
    expect(edit(1)).toEqual({
        action: '/posts/1/edit',
        method: 'get',
        _method: 'get'
    })
})
