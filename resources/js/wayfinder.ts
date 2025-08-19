export type QueryParams = Record<
    string,
    | string
    | number
    | boolean
    | string[]
    | null
    | undefined
    | Record<string, string | number | boolean>
>;

type Method = "get" | "post" | "put" | "delete" | "patch" | "head";

declare global {
    interface Window {
        Wayfinder: {
            defaultParameters: Record<string, unknown>;
        };
    }
}
window.Wayfinder = {
    defaultParameters: {},
};

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

export const queryParams = (options?: RouteQueryOptions) => {
    if (!options || (!options.query && !options.mergeQuery)) {
        return "";
    }

    const query = options.query ?? options.mergeQuery;
    const includeExisting = options.mergeQuery !== undefined;

    const getValue = (value: string | number | boolean) => {
        if (value === true) {
            return "1";
        }

        if (value === false) {
            return "0";
        }

        return value.toString();
    };

    const params = new URLSearchParams(
        includeExisting && typeof window !== "undefined"
            ? window.location.search
            : "",
    );

    for (const key in query) {
        if (query[key] === undefined || query[key] === null) {
            params.delete(key);
            continue;
        }

        if (Array.isArray(query[key])) {
            if (params.has(`${key}[]`)) {
                params.delete(`${key}[]`);
            }

            query[key].forEach((value) => {
                params.append(`${key}[]`, value.toString());
            });
        } else if (typeof query[key] === "object") {
            params.forEach((_, paramKey) => {
                if (paramKey.startsWith(`${key}[`)) {
                    params.delete(paramKey);
                }
            });

            for (const subKey in query[key]) {
                if (
                    ["string", "number", "boolean"].includes(
                        typeof query[key][subKey],
                    )
                ) {
                    params.set(
                        `${key}[${subKey}]`,
                        getValue(query[key][subKey]),
                    );
                }
            }
        } else {
            params.set(key, getValue(query[key]));
        }
    }

    const str = params.toString();

    return str.length > 0 ? `?${str}` : "";
};

export const setUrlDefaults = (params: Record<string, unknown>) => {
    window.Wayfinder.defaultParameters = params;
};

export const addUrlDefault = (
    key: string,
    value: string | number | boolean,
) => {
    window.Wayfinder.defaultParameters[key] = value;
};

export const applyUrlDefaults = (existing: Record<string, unknown>) => {
    const existingParams = { ...existing };

    for (const key in window.Wayfinder.defaultParameters) {
        if (
            existingParams[key] === undefined &&
            window.Wayfinder.defaultParameters[key] !== undefined
        ) {
            existingParams[key] = window.Wayfinder.defaultParameters[key];
        }
    }

    return existingParams;
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
