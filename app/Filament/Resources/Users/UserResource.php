<?php

namespace App\Filament\Resources\Users;

use App\Enums\OrderStatus;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Customers';

    protected static ?string $navigationLabel = 'Customers & staff';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email', 'phone'];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                TextInput::make('name')->required(),
                TextInput::make('phone')->tel()->unique(ignoreRecord: true),
                TextInput::make('email')->email()->unique(ignoreRecord: true),
                TextInput::make('password')->password()->revealable()
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $operation) => $operation === 'create')
                    ->helperText(fn (string $operation) => $operation === 'edit' ? 'Leave empty to keep the current password.' : null),
                TextInput::make('address'),
                Select::make('city')->options(array_combine(config('shop.districts'), config('shop.districts')))->searchable(),
                Toggle::make('is_admin')->label('Admin access (can log in to this panel)')
                    ->disabled(fn (?User $record) => $record?->id === auth()->id())
                    ->dehydrated(fn (?User $record) => $record?->id !== auth()->id()),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->withCount('orders')->withSum(['orders as spent' => fn ($q) => $q->where('status', OrderStatus::Delivered)], 'total'))
            ->columns([
                TextColumn::make('name')->searchable()->weight('medium')->description(fn (User $r) => $r->phone),
                TextColumn::make('email')->searchable()->placeholder('—')->toggleable(),
                TextColumn::make('city')->placeholder('—')->toggleable(),
                TextColumn::make('orders_count')->label('Orders')->badge()->sortable(),
                TextColumn::make('spent')->label('Spent')->formatStateUsing(fn ($state) => money($state))->sortable(),
                IconColumn::make('is_admin')->label('Admin')->boolean(),
                TextColumn::make('created_at')->label('Joined')->since()->sortable(),
            ])
            ->filters([TernaryFilter::make('is_admin')->label('Admins')])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
