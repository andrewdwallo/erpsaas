import preset from '../../../../vendor/filament/filament/tailwind.config.preset'

export default {
    presets: [preset],
    content: [
        './app/Filament/Company/**/*.php',
        './resources/views/**/*.blade.php',
        './resources/views/filament/company/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
        './vendor/andrewdwallo/filament-companies/resources/views/**/*.blade.php',
    ],
    theme: {
        extend: {
            colors: {
                white: '#F6F5F3',
                platinum: '#E8E9EB',
                moonlight: '#F6F5F3',
                translucent: 'rgba(54, 54, 52, 0.06)',
            }
        }
    }
}
