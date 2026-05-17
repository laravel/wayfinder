export type QueryParams = {
    [key: string]:
        | string
        | number
        | boolean
        | (string | number)[]
        | null
        | undefined
        | QueryParams;
};

type Method = "get" | "post" | "put" | "delete" | "patch" | "head" | "options";
type ParamValue = string | number | boolean;
type UrlDefaults = Record<string, unknown>;

type RouteContractKey = "__wayfinderRouteContract";

export type RouteContract<TRequest = unknown, TResponse = unknown> = {
    readonly __wayfinderRouteContract?: {
        request: TRequest;
        response: TResponse;
    };
};

let urlDefaults: () => UrlDefaults = () => ({});

export type RouteDefinition<
    TMethod extends Method | Method[],
    TComponent extends string | Record<string, string> | undefined = undefined,
    TRequest = unknown,
    TResponse = unknown,
> = {
    url: string;
    component?: TComponent;
} & RouteContract<TRequest, TResponse> &
    (TMethod extends Method[] ? { methods: TMethod } : { method: TMethod });

export type RouteFormDefinition<
    TMethod extends Method,
    TComponent extends string | Record<string, string> | undefined = undefined,
    TRequest = unknown,
    TResponse = unknown,
> = {
    action: string;
    method: TMethod;
    component?: TComponent;
} & RouteContract<TRequest, TResponse>;

export type RouteQueryOptions = {
    query?: QueryParams;
    mergeQuery?: QueryParams;
};

type RouteContractOf<T> =
    T extends (...args: infer TArguments) => infer TReturn
        ? TArguments extends unknown[]
            ? RouteContractOf<TReturn>
            : never
        : RouteContractKey extends keyof T
          ? T extends RouteContract<infer TRequest, infer TResponse>
              ? {
                    request: TRequest;
                    response: TResponse;
                }
              : never
          : never;

export type RequestOf<T> =
    [RouteContractOf<T>] extends [never]
        ? never
        : RouteContractOf<T> extends { request: infer TRequest }
          ? TRequest
          : never;

export type ResponseOf<T> =
    [RouteContractOf<T>] extends [never]
        ? never
        : RouteContractOf<T> extends { response: infer TResponse }
          ? TResponse
          : never;

export const formSafeOptions = (
    method: Method,
    options?: RouteQueryOptions,
): RouteQueryOptions => ({
    [options?.mergeQuery ? "mergeQuery" : "query"]: {
        _method: method.toUpperCase(),
        ...(options?.query ?? options?.mergeQuery),
    },
});

const getValue = (value: ParamValue) => {
    if (value === true) {
        return "1";
    }

    if (value === false) {
        return "0";
    }

    return value.toString();
};

const addNestedParams = (
    obj: QueryParams,
    prefix: string,
    params: URLSearchParams,
) => {
    Object.entries(obj).forEach(([subKey, value]) => {
        if (value === undefined) {
            return;
        }

        const paramKey = `${prefix}[${subKey}]`;

        if (Array.isArray(value)) {
            value.forEach((v) => params.append(`${paramKey}[]`, getValue(v)));
        } else if (value !== null && typeof value === "object") {
            addNestedParams(value, paramKey, params);
        } else if (["string", "number", "boolean"].includes(typeof value)) {
            params.set(paramKey, getValue(value as string | number | boolean));
        }
    });
};

const clearParamFamily = (params: URLSearchParams, key: string) => {
    const toDelete = new Set<string>();

    params.forEach((_, paramKey) => {
        if (paramKey === key || paramKey.startsWith(`${key}[`)) {
            toDelete.add(paramKey);
        }
    });

    toDelete.forEach((paramKey) => params.delete(paramKey));
};

export const queryParams = (options?: RouteQueryOptions) => {
    if (!options || (!options.query && !options.mergeQuery)) {
        return "";
    }

    const query = options.query ?? options.mergeQuery;
    const includeExisting = options.mergeQuery !== undefined;

    const params = new URLSearchParams(
        includeExisting && typeof window !== "undefined"
            ? window.location.search
            : "",
    );

    for (const key in query) {
        const queryValue = query[key];

        if (includeExisting) {
            clearParamFamily(params, key);
        }

        if (queryValue === undefined || queryValue === null) {
            continue;
        }

        if (Array.isArray(queryValue)) {
            queryValue.forEach((value) => {
                params.append(`${key}[]`, value.toString());
            });
        } else if (typeof queryValue === "object") {
            addNestedParams(queryValue, key, params);
        } else {
            params.set(key, getValue(queryValue));
        }
    }

    const str = params.toString();

    return str.length > 0 ? `?${str}` : "";
};

export const validateParameters = (
    args: Record<string, unknown> | undefined,
    optional: string[],
) => {
    const missing = optional.filter((key) => {
        const value = args?.[key];

        return (
            value === undefined ||
            value === null ||
            value === "" ||
            value === false
        );
    });
    const expectedMissing = optional.slice(missing.length * -1);

    for (let i = 0; i < missing.length; i++) {
        if (missing[i] !== expectedMissing[i]) {
            throw Error(
                "Unexpected optional parameters missing. Unable to generate a URL.",
            );
        }
    }
};

export const setUrlDefaults = (params: UrlDefaults | (() => UrlDefaults)) => {
    urlDefaults = typeof params === "function" ? params : () => params;
};

export const addUrlDefault = (
    key: string,
    value: string | number | boolean,
) => {
    const previousDefaults = urlDefaults;

    urlDefaults = () => ({
        ...previousDefaults(),
        [key]: value,
    });
};

export const applyUrlDefaults = <T extends UrlDefaults | undefined>(
    existing: T,
): T => {
    const existingParams = { ...(existing ?? ({} as UrlDefaults)) };
    const defaultParams = urlDefaults();

    for (const key in defaultParams) {
        if (
            existingParams[key] === undefined &&
            defaultParams[key] !== undefined
        ) {
            (existingParams as Record<string, unknown>)[key] =
                defaultParams[key];
        }
    }

    return existingParams as T;
};
