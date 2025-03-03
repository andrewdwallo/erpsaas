@php
    $user = filament()->auth()->user();
    $items = filament()->getUserMenuItems();
    $logoutItem = $items['logout'] ?? null;
    $currentTenant = filament()->getTenant();
    $currentTenantName = $currentTenant ? filament()->getTenantName($currentTenant) : null;

    $navigation = $component->getNavigation();
    $hasDisplayAndAccessibility = $component->hasDisplayAndAccessibility();
    $hasCompanySettings = $component->hasCompanySettings();
    $hasLogoutItem = $component->hasLogoutItem();
    $panels = $component->getNavigationAsHierarchyArray();
@endphp

<div x-data="panelShiftDropdown" x-on:click.outside="closeDropdown">
    <div x-on:click="toggleDropdown" class="flex cursor-pointer">
        <button
            type="button"
            class="flex items-center justify-center gap-x-3 rounded-lg p-2 text-sm font-medium outline-none transition duration-75 hover:bg-gray-100 focus-visible:bg-gray-100 dark:hover:bg-white/5 dark:focus-visible:bg-white/5"
        >
            @if($currentTenant)
                <x-filament-panels::avatar.tenant
                    :tenant="$currentTenant"
                    class="shrink-0"
                />
            @else
                <x-filament-panels::avatar.user
                    :user="$user"
                    class="shrink-0"
                />
            @endif

            <span class="grid justify-items-start text-start">
                @if ($currentTenant instanceof \Filament\Models\Contracts\HasCurrentTenantLabel)
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $currentTenant->getCurrentTenantLabel() }}
                    </span>
                @endif

                <span class="text-gray-950 dark:text-white">
                    {{ $currentTenantName ?? filament()->getUserName($user) }}
                </span>
            </span>

            <x-filament::icon
                icon="heroicon-m-chevron-down"
                class="h-5 w-5 transition duration-75 text-gray-400 group-hover:text-gray-500 group-focus-visible:text-gray-500 dark:text-gray-500 dark:group-hover:text-gray-400 dark:group-focus-visible:text-gray-400"
                x-bind:class="{ 'rotate-180': open }"
            />
        </button>
    </div>
    <div x-show="open"
         class="flex flex-col transition duration-200 ease-in-out grow shrink mt-4 absolute z-10 w-screen max-w-[360px] end-8 rounded-lg bg-white shadow-lg ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 overflow-hidden">
        @foreach($panels as $panelId => $panel)
            <x-panel-shift-dropdown.panel :panel-id="$panelId">
                @if($panelId !== 'main' && isset($panel['label']))
                    <x-panel-shift-dropdown.subpanel-header :label="$panel['label']" :panel-id="$panelId"/>
                @endif
                @if($panel['renderItems'])
                    @foreach($panel['items'] as $item)
                        <x-panel-shift-dropdown.content-handler :item="$item"/>
                    @endforeach
                @endif
                @if($panelId === 'company-settings' && $currentTenant)
                    <x-panel-shift-dropdown.company-settings :current-tenant="$currentTenant"
                                                             icon="heroicon-m-building-office-2"/>
                @endif
                @if($panelId === 'company-switcher' && $currentTenant)
                    <x-panel-shift-dropdown.company-switcher :current-tenant="$currentTenant"
                                                             icon="heroicon-m-adjustments-horizontal"/>
                @endif
                @if($panelId === 'display-and-accessibility')
                    <x-panel-shift-dropdown.display-accessibility icon="heroicon-s-moon"/>
                @endif
                @if($panelId === 'main' && $hasLogoutItem)
                    <x-panel-shift-dropdown.item
                        tag="form"
                        method="post"
                        :action="$logoutItem?->getUrl() ?? filament()->getLogoutUrl()"
                        :label="$logoutItem?->getLabel() ?? __('filament-panels::layout.actions.logout.label')"
                        :icon="$logoutItem?->getIcon() ?? \Filament\Support\Facades\FilamentIcon::resolve('panels::user-menu.logout-button') ?? 'heroicon-m-arrow-left-on-rectangle'"
                    />
                @endif
            </x-panel-shift-dropdown.panel>
        @endforeach
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('panelShiftDropdown', () => ({
            open: false,
            navigationStack: ['main'],
            theme: localStorage.getItem('theme') || '{{ filament()->getDefaultThemeMode()->value }}',
            themeLabels: {
                light: 'Off',
                dark: 'On',
                system: 'System',
            },

            toggleDropdown() {
                this.open = !this.open;
            },

            closeDropdown() {
                this.open = false;
            },

            setActiveMenu(menu) {
                this.transitionPanel(menu, 'forward');
            },

            focusMenuItem(menuItemRef) {
                this.$nextTick(() => {
                    setTimeout(() => {
                        this.$refs[menuItemRef]?.focus();
                    }, 200);
                });
            },

            focusBackButton(backButtonRef) {
                this.$nextTick(() => {
                    setTimeout(() => {
                        this.$refs[backButtonRef]?.focus();
                    }, 200);
                });
            },

            goBack() {
                if (this.open && this.navigationStack.length > 1) {
                    this.transitionPanel(this.navigationStack.at(-2), 'back');
                }
            },

            currentActiveMenu() {
                return this.navigationStack.at(-1);
            },

            transitionPanel(target, direction) {
                const currentPanel = this.$refs[this.currentActiveMenu()];
                const targetPanel = this.$refs[target];

                const translateX = direction === 'forward' ? '-100%' : '100%';
                currentPanel.style.transform = `translateX(${translateX})`;

                setTimeout(() => {
                    currentPanel.classList.add('hide');
                    targetPanel.classList.remove('hide');
                    targetPanel.style.transform = 'translateX(0)';

                    if (direction === 'forward') {
                        this.navigationStack.push(target);
                    } else {
                        this.navigationStack.pop();
                    }
                }, 200);
            },

            setTheme(newTheme) {
                this.theme = newTheme;
            },

            init() {
                this.$watch('theme', (value) => {
                    this.$dispatch('theme-changed', value);
                });

                this.$watch('open', (value) => {
                    if (value) {
                        if (this.navigationStack.length === 1) {
                            const mainPanel = this.$refs.main;
                            mainPanel.classList.remove('hide');
                            mainPanel.style.transform = 'translateX(0)';
                        }
                    } else {
                        if (this.currentActiveMenu() !== 'main') {
                            this.setActiveMenu('main');
                        }
                    }
                });
            },

            getThemeLabel(value) {
                return this.themeLabels[value] || value;
            },
        }));
    });
</script>
