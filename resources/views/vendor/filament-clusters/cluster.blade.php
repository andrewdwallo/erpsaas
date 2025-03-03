<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <x-filament::input.wrapper
        @class([
            "guava-fi-cl-cluster",
            ...$field->getResponsiveClasses(),
        ])
    >
        {{ $getChildComponentContainer() }}
    </x-filament::input.wrapper>


    @foreach($field->getChildComponents() as $child)
        @if ($childStatePath = $child->getStatePath())
            @if($errors->has($childStatePath) )
                <x-filament-forms::field-wrapper.error-message>
                    {{ $errors->first($childStatePath) }}
                </x-filament-forms::field-wrapper.error-message>
            @endif
        @endif
    @endforeach
</x-dynamic-component>
