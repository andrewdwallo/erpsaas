@php
    $isContained = $isContained();
    $statePath = $getStatePath();
    $previousAction = $getAction('previous');
    $nextAction = $getAction('next');
    $currentStepDescription = $getCurrentStepDescription();
    $areStepTabsHidden = $areStepTabsHidden();
@endphp

<div
    wire:ignore.self
    x-cloak
    x-data="{
        step: null,

        nextStep: function () {
            let nextStepIndex = this.getStepIndex(this.step) + 1

            if (nextStepIndex >= this.getSteps().length) {
                return
            }

            this.step = this.getSteps()[nextStepIndex]

            this.autofocusFields()
            this.scroll()
        },

        previousStep: function () {
            let previousStepIndex = this.getStepIndex(this.step) - 1

            if (previousStepIndex < 0) {
                return
            }

            this.step = this.getSteps()[previousStepIndex]

            this.autofocusFields()
            this.scroll()
        },

        scroll: function () {
            this.$nextTick(() => {
                this.$refs.header.children[
                    this.getStepIndex(this.step)
                ].scrollIntoView({ behavior: 'smooth', block: 'start' })
            })
        },

        autofocusFields: function () {
            $nextTick(() =>
                this.$refs[`step-${this.step}`]
                    .querySelector('[autofocus]')
                    ?.focus(),
            )
        },

        getStepIndex: function (step) {
            let index = this.getSteps().findIndex(
                (indexedStep) => indexedStep === step,
            )

            if (index === -1) {
                return 0
            }

            return index
        },

        getSteps: function () {
            return JSON.parse(this.$refs.stepsData.value)
        },

        isFirstStep: function () {
            return this.getStepIndex(this.step) <= 0
        },

        isLastStep: function () {
            return this.getStepIndex(this.step) + 1 >= this.getSteps().length
        },

        isStepAccessible: function (stepId) {
            return (
                @js($isSkippable()) || this.getStepIndex(this.step) > this.getStepIndex(stepId)
            )
        },

        updateQueryString: function () {
            if (! @js($isStepPersistedInQueryString())) {
                return
            }

            const url = new URL(window.location.href)
            url.searchParams.set(@js($getStepQueryStringKey()), this.step)

            history.pushState(null, document.title, url.toString())
        },
    }"
    x-init="
        $watch('step', () => updateQueryString())

        step = getSteps().at({{ $getStartStep() - 1 }})

        autofocusFields()
    "
    x-on:next-wizard-step.window="if ($event.detail.statePath === '{{ $statePath }}') nextStep()"
    {{
        $attributes
            ->merge([
                'id' => $getId(),
            ], escape: false)
            ->merge($getExtraAttributes(), escape: false)
            ->merge($getExtraAlpineAttributes(), escape: false)
            ->class([
                'fi-fo-wizard',
                'fi-contained rounded-xl bg-white shadow-xs ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10' => $isContained,
            ])
    }}
>
    <input
        type="hidden"
        value="{{
            collect($getChildComponentContainer()->getComponents())
                ->filter(static fn (\Filament\Forms\Components\Wizard\Step $step): bool => $step->isVisible())
                ->map(static fn (\Filament\Forms\Components\Wizard\Step $step) => $step->getId())
                ->values()
                ->toJson()
        }}"
        x-ref="stepsData"
    />

    @foreach ($getChildComponentContainer()->getComponents() as $step)
        <div
            x-ref="step-{{ $step->getId() }}"
            wire:key="{{ $this->getId() }}.{{ $statePath }}.{{ $step->getId() }}.step"
            x-bind:class="{ 'hidden': step !== '{{ $step->getId() }}' }"
        >
            <x-filament::section
                :id="'wizard-step-{{ $step->getId() }}'"
                :icon="$step->getIcon()"
            >
                @if (!$step->isLabelHidden())
                    <x-slot name="heading">
                        {{ $step->getLabel() }}
                    </x-slot>
                @endif

                @if (filled($description = $step->getDescription()))
                    <x-slot name="description">
                        {{ $description }}
                    </x-slot>
                @endif

                {{ $step->getChildComponentContainer() }}

                <footer
                    @class([
                        'fi-section-footer py-6',
                    ])
                >
                    <div
                        @class([
                            'flex items-center justify-between gap-x-3',
                        ])
                    >
                        <span
                            x-cloak
                            @if (! $previousAction->isDisabled())
                                x-on:click="previousStep"
                            @endif
                            x-show="! isFirstStep()"
                        >
                            {{ $previousAction }}
                        </span>

                        <span x-show="isFirstStep()">
                            {{ $getCancelAction() }}
                        </span>

                        <span
                            x-cloak
                            @if (! $nextAction->isDisabled())
                                x-on:click="
                                    $wire.dispatchFormEvent(
                                        'wizard::nextStep',
                                        '{{ $statePath }}',
                                        getStepIndex(step),
                                    )
                                "
                            @endif
                            x-bind:class="{ 'hidden': isLastStep(), 'block': ! isLastStep() }"
                        >
                            {{ $nextAction }}
                        </span>

                        <span
                            x-bind:class="{ 'hidden': ! isLastStep(), 'block': isLastStep() }"
                        >
                            {{ $getSubmitAction() }}
                        </span>
                    </div>
                </footer>
            </x-filament::section>
        </div>
    @endforeach
</div>
