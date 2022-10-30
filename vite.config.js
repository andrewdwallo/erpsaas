import { defineConfig, splitVendorChunkPlugin } from 'vite'
import laravel from 'laravel-vite-plugin'
import fs from 'fs'
import { homedir } from 'os'
import { resolve } from 'path'

let host = 'erpsaas.dev'

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/filament/filament-stimulus.js',
                'resources/filament/filament-turbo.js',
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/filament.css',
            ],
            refresh: true,
        }),
        splitVendorChunkPlugin(),
    ],
    build: {
        rollupOptions: {
            external: [
                "../../node_modules/@types/**/*",
                "../../vendor/filament/forms/dist/module.esm.css",
                "../../vendor/filament/filament/resources/css/app.css",
                "../../vendor/filament/forms/dist/module.esm",
                "../../vendor/filament/notifications/dist/module.esm",
            ],
            output: {
                manualChunks(id) {
                    if (id.includes('node_modules')) {
                        return id.toString().split('node_modules/')[1].split('/')[0].toString();
                    }
                }
            }
        }
    },
    server: detectServerConfig(host),
});

function detectServerConfig(host) {
    let keyPath = resolve(homedir(), `.config/valet/Certificates/${host}.key`)
    let certificatePath = resolve(homedir(), `.config/valet/Certificates/${host}.crt`)

    if (! fs.existsSync(keyPath)) {
        return {}
    }

    if (! fs.existsSync(certificatePath)) {
        return {}
    }

    return {
        hmr: { host },
        host,
        https: {
            key: fs.readFileSync(keyPath),
            cert: fs.readFileSync(certificatePath),
        },
    }
}
