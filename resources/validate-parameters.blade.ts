const validateParameters = (args: Record<string, unknown>|undefined, optional: string[]) => {
    const missing = optional.filter((key) => ! args?.[key])
    const expectedMissing = optional.slice(missing.length * -1)

    for (let i = 0; i < missing.length; i++) {
        if (missing[i] !== expectedMissing[i]) {
            throw Error('Unexpected optional parameters missing. Unable to generate a URL.')
        }
    }
}

