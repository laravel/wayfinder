const queryParams = (
    query?: Record<
        string,
        | string
        | number
        | boolean
        | string[]
        | null
        | undefined
        | Record<string, string | number | boolean>
    >,
) => {
    if (!query) {
        return "";
    }

    const getValue = (value: string | number | boolean) => {
        if (value === true) {
            return "1";
        }

        if (value === false) {
            return "0";
        }

        return value.toString();
    };

    const includeExisting = query["*"] ?? false;
    delete query["*"];

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
                params.set(`${key}[${subKey}]`, getValue(query[key][subKey]));
            }
        } else {
            params.set(key, getValue(query[key]));
        }
    }

    const str = params.toString();

    return str.length > 0 ? `?${str}` : "";
};
