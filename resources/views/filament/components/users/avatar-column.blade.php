<div class="px-4 py-3">
    <div class="flex items-center">
        <div style="height: 40px; width: 40px;" class="overflow-hidden rounded-full">
            <img src="{{ $getRecord()->profile_photo_url }}" alt="{{ $getRecord()->name }}" style="height: 40px; width: 40px;" class="object-cover object-center">
        </div>
        <div class="ml-4 font-semibold">
            <div class="inline-flex items-center space-x-1 rtl:space-x-reverse">
                <span>
                    {{ $getRecord()->name }}
                </span>
            </div>

            <div class="text-sm text-gray-500">
                <p><a href="mailto:{{ $getRecord()->email }}">{{ $getRecord()->email }}</a></p>
            </div>
        </div>
    </div>
</div>