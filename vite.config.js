import { defineConfig } from 'vite'

export default defineConfig({
    test: {
        include: ['tests/*.test.ts'],
        environment: 'happy-dom',
        globalSetup: 'build.ts',
    },
    resolve: {
        alias: {
            '@actions/': './workbench/resources/js/actions'
        }
    }
})
