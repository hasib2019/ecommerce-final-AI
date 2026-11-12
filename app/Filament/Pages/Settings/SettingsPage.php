<?php

namespace App\Filament\Pages\Settings;

use App\Support\CacheVersion;
use App\Support\Settings;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Support\Components\Attributes\Unexposed;
use UnitEnum;

/**
 * Base for all settings screens. Each page edits one or more setting "groups"
 * (general, header, footer…) – field names are "group.key".
 */
abstract class SettingsPage extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    public ?array $data = [];

    /** @return array<int, string> */
    abstract protected function groups(): array;

    /** @return array<int, \Filament\Schemas\Components\Component|\Filament\Forms\Components\Field> */
    abstract protected function fields(): array;

    public function mount(): void
    {
        $state = [];
        foreach ($this->groups() as $group) {
            $state[$group] = Settings::group($group);
        }

        $this->form->fill($state);
    }

    #[Unexposed]
    public function form(Schema $schema): Schema
    {
        return $schema->components($this->fields())->statePath('data');
    }

    #[Unexposed]
    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label('Save changes')->submit('save')->keyBindings(['mod+s'])->icon('heroicon-o-check'),
                        Action::make('viewStore')->label('View store')->color('gray')->url(url('/'), shouldOpenInNewTab: true),
                    ])->sticky(),
                ]),
        ]);
    }

    public function save(): void
    {
        $state = $this->form->getState();

        foreach ($this->groups() as $group) {
            Settings::save($group, (array) ($state[$group] ?? []));
        }

        CacheVersion::bump();

        Notification::make()->title('Settings saved')->body('Your store has been updated.')->success()->send();
    }
}
