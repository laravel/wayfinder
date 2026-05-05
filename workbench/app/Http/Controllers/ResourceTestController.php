<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryJsonApiResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\UserJsonApiResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\WrappedProductResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

class ResourceTestController
{
    public function show(): JsonResource
    {
        return new ProductResource(null);
    }

    public function index(): JsonResource
    {
        return ProductResource::collection([]);
    }

    public function wrapped(): JsonResource
    {
        return new WrappedProductResource(null);
    }

    public function jsonApi(): JsonApiResource
    {
        return new CategoryJsonApiResource(null);
    }

    public function jsonApiCollection(): JsonApiResource
    {
        return CategoryJsonApiResource::collection([]);
    }

    public function user(): JsonResource
    {
        return new UserResource(null);
    }

    public function users(): JsonResource
    {
        return UserResource::collection([]);
    }

    public function userJsonApi(): JsonApiResource
    {
        return new UserJsonApiResource(null);
    }
}
