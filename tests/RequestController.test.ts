import { describe, expectTypeOf, test } from "vitest";

import type { StorePostRequest, UpdatePostRequest } from '../workbench/resources/js/actions/App/Http/Controllers/RequestController'

describe("request validation", () => {
    test("store request data structure", () => {
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

    test("update request data structure", () => {
        const data: UpdatePostRequest = {
            name: 'name',
            description: 'description',
            price: 5,
            hidden: true,
            stock: 10,
            catalog_id: 2,
            code: 'code',
            slug: 'name',
            image: new File([], 'image')
        }
        expectTypeOf(data).toMatchObjectType<UpdatePostRequest>
    });
})