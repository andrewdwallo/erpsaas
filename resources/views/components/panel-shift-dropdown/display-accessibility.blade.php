@props([
    'icon' => null,
])

<li class="grid grid-flow-col auto-cols-auto gap-x-2 items-start p-2">
    <div class="icon h-9 w-9 flex items-center justify-center rounded-full bg-gray-200 dark:bg-white/10">
        <x-filament::icon
            :icon="$icon"
            class="h-6 w-6 text-gray-600 dark:text-gray-200"
        />
    </div>
    <div>
        <div class="px-2 pb-2">
            <h2 class="text-gray-800 dark:text-gray-200 text-base font-semibold">
                {{ __('Dark mode') }}
            </h2>
            <p class="text-sm font-normal text-gray-500 dark:text-gray-400">
                {{ __('Adjust the appearance to reduce glare and give your eyes a break.') }}
            </p>
        </div>
        <!-- Custom radio buttons for Theme setting -->
        <template x-for="(label, value) in themeLabels" :key="value">
            <div class="cursor-pointer p-2 rounded-lg hover:bg-gray-50 focus-visible:bg-gray-50 dark:hover:bg-white/5 dark:focus-visible:bg-white/5" x-on:click="setTheme(value)">
                <label class="text-sm font-medium flex items-center justify-between cursor-pointer">
                    <span x-text="label"></span>
                    <input type="radio" class="sr-only" :id="'theme' + value" :name="'theme'" :value="value" x-model="theme">
                    <span
                        class="h-3 w-3 ring-2 rounded-full"
                        x-bind:class="{
                            'border-2 border-white dark:border-gray-800 bg-primary-500 dark:bg-primary-400 ring-primary-500 dark:ring-primary-400': theme === value,
                            'ring-gray-400 dark:ring-gray-600': theme !== value
                        }"
                    ></span>
                </label>
            </div>
        </template>
    </div>
</li>
