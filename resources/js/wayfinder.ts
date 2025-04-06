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

export const queryParams = (options?: {
    query?: QueryParams;
    mergeQuery?: QueryParams;
}) => {
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
                if (['string', 'number', 'boolean'].includes(typeof query[key][subKey])) {
                    params.set(`${key}[${subKey}]`, getValue(query[key][subKey]));
                }
            }
        } else {
            params.set(key, getValue(query[key]));
        }
    }

    const str = params.toString();

    return str.length > 0 ? `?${str}` : "";
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
