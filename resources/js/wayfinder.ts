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

let urlDefaults: Record<string, unknown> = {};

export type RouteDefinition<TMethod extends Method | Method[]> = {
    url: string;
} & (TMethod extends Method[] ? { methods: TMethod } : { method: TMethod });

export type RouteFormDefinition<TMethod extends Method> = {
    action: string;
    method: TMethod;
};

export type RouteQueryOptions = {
    query?: QueryParams;
    mergeQuery?: QueryParams;
};

const getValue = (value: string | number | boolean) => {
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
        if (value === undefined) return;

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

        if (queryValue === undefined || queryValue === null) {
            params.delete(key);
            continue;
        }

        if (Array.isArray(queryValue)) {
            if (params.has(`${key}[]`)) {
                params.delete(`${key}[]`);
            }

            queryValue.forEach((value) => {
                params.append(`${key}[]`, value.toString());
            });
        } else if (typeof queryValue === "object") {
            params.forEach((_, paramKey) => {
                if (paramKey.startsWith(`${key}[`)) {
                    params.delete(paramKey);
                }
            });

            addNestedParams(queryValue, key, params);
        } else {
            params.set(key, getValue(queryValue));
        }
    }

    const str = params.toString();

    return str.length > 0 ? `?${str}` : "";
};

export const setUrlDefaults = (params: Record<string, unknown>) => {
    urlDefaults = params;
};

export const addUrlDefault = (
    key: string,
    value: string | number | boolean,
) => {
    urlDefaults[key] = value;
};

export const applyUrlDefaults = <T extends Record<string, unknown> | undefined>(
    existing: T,
): T => {
    const existingParams = { ...(existing ?? ({} as Record<string, unknown>)) };

    for (const key in urlDefaults) {
        if (
            existingParams[key] === undefined &&
            urlDefaults[key] !== undefined
        ) {
            (existingParams as Record<string, unknown>)[key] = urlDefaults[key];
        }
    }

    return existingParams as T;
};

export const validateParameters = (
    args: Record<string, unknown> | undefined,
    optional: string[],
) => {
    const missing = optional.filter((key) => !args?.[key]);
    const expectedMissing = optional.slice(missing.length * -1);

    for (let i = 0; i < missing.length; i++) {
        if (missing[i] !== expectedMissing[i]) {
            throw Error(
                "Unexpected optional parameters missing. Unable to generate a URL.",
            );
        }
    }
};
