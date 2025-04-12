import { describe, expectTypeOf, test } from "vitest";

import type { StorePostRequest, UpdatePostRequest } from '../workbench/resources/js/actions/App/Http/Controllers/RequestController'

// test for request, request param in different order (not first), request rules as strings, request rules as instances

describe("model", () => {
    test("model structure", () => {
        const data: StorePostRequest = {
            name: 'name',
            description: 'description',
            price: 5,
            hidden: false,
            stock: 10,
            catalog_id: 2,
            code: 'code',
            slug: 'name'
        }
        expectTypeOf(data).toMatchObjectType<StorePostRequest>
    });

    test("model structure", () => {
        const data: UpdatePostRequest = {
            name: 'name',
            description: 'description',
            price: 5,
            hidden: true,
            stock: 10,
            catalog_id: 2,
            code: 'code',
            slug: 'name',
            image: 'data'
        }
        expectTypeOf(data).toMatchObjectType<StorePostRequest>
    });
})