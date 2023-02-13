<?php

namespace App\Http\Livewire;

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\HtmlString;
use Livewire\Component;

class Register extends Component implements HasForms
{
    use InteractsWithForms;

    public User $user;

    public $name = '';

    public $email = '';

    public $password = '';

    public $passwordConfirmation = '';

    public $company_name = '';

    public $website = '';

    public $address = '';

    public $logo = '';

    public function mount(): void
    {
        $this->form->fill();
    }

    protected function getFormSchema(): array
    {
        return [
            Wizard::make([
                Wizard\Step::make('Personal Information')
                ->icon('heroicon-o-user')
                ->schema([
                    TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(50),
                    TextInput::make('email')
                    ->label('Email Address')
                    ->email()
                    ->required()
                    ->maxLength(50)
                    ->unique(User::class),
                    TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->required()
                    ->maxLength(50)
                    ->minLength(8)
                    ->same('passwordConfirmation')
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state)),
                    TextInput::make('passwordConfirmation')
                    ->label('Confirm Password')
                    ->password()
                    ->required()
                    ->maxLength(50)
                    ->minLength(8)
                    ->dehydrated(false),
                ])
                ->columns([
                    'sm' => 2,
                ])
                ->columnSpan([
                    'sm' => 2,
                ]),
                Wizard\Step::make('Company Information')
                ->schema([
                    TextInput::make('company_name')->required()->maxLength(100)->autofocus(),
                    TextInput::make('website')->prefix('https://')->maxLength(250),
                    TextInput::make('address')->maxLength(250),
                    FileUpload::make('logo')->image()->directory('logos'),
                ]),
            ])
            ->columns([
                'sm' => 1,
            ])
            ->columnSpan([
                'sm' => 1,
            ])
            ->submitAction(new HtmlString(html: '<button type="submit" class="filament-button filament-button-size-sm inline-flex items-center justify-center py-1 gap-1 font-medium rounded-lg border transition-colors focus:outline-none focus:ring-offset-2 focus:ring-inset min-h-[2rem] px-3 text-sm text-white shadow focus:ring-white border-transparent bg-primary-600 hover:bg-primary-500 focus:bg-primary-700 focus:ring-offset-primary-700">Register</button>')),

        ];
    }

    public function register()
    {
        $user = User::create($this->form->getState());
        Filament::auth()->login(user: $user, remember:true);

        return redirect()->intended(Filament::getUrl('filament.pages.dashboard'));
    }

    public function render(): View
    {
        return view('livewire.register');
    }
}
