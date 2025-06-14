@props([
    'shape' => 'square',
    'size' => 'lg',
])

<img {{
    $attributes
        ->class([
            'doc-template-logo object-contain',
            match ($size) {
                'sm' => 'max-h-8',
                'md' => 'max-h-16',
                'lg' => 'max-h-24',
                'xl' => 'max-h-32',
                default => $size,
            },
            match ($shape) {
                'square' => 'rounded-none',
                'rounded-sm' => 'rounded-md',
                'circle' => 'rounded-full',
                default => $shape,
            },
        ])
}}
/>

