@props([
    'shape' => 'square',
    'size' => 'md',
])

<img {{
    $attributes
        ->class([
            'inv-logo object-contain',
            match ($size) {
                'sm' => 'max-h-8',
                'md' => 'max-h-16',
                'lg' => 'max-h-24',
                'xl' => 'max-h-32',
                default => $size,
            },
            match ($shape) {
                'square' => 'rounded-none',
                'rounded' => 'rounded-md',
                'circle' => 'rounded-full',
                default => $shape,
            },
        ])
}}
/>

