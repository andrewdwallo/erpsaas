@props(['title','description'])
<div {{$attributes->class(["grid grid-cols-2 gap-6 filament-breezy-grid-section"])}}>

    <div class="col-span-2 sm:col-span-1 flex justify-between">
        <div class="px-4 sm:px-0">
            <h3 @class(['text-lg font-medium text-gray-900 filament-breezy-grid-title','dark:text-white'=>config('filament.dark_mode')])>{{$title}}</h3>

            <p @class(['mt-1 text-sm text-gray-600 filament-breezy-grid-description','dark:text-gray-100'=>config('filament.dark_mode')])>
                {{$description}}
            </p>
        </div>
    </div>

    {{$slot}}

</div>
