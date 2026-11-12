<?php

namespace App\Filament\Resources\Coupons;

use App\Filament\Resources\Coupons\Pages\CreateCoupon;
use App\Filament\Resources\Coupons\Pages\EditCoupon;
use App\Filament\Resources\Coupons\Pages\ListCoupons;
use App\Models\Coupon;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static string|UnitEnum|null $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Discount')->columns(2)->schema([
                TextInput::make('code')->required()->maxLength(50)->unique(ignoreRecord: true)
                    ->default(fn () => Str::upper(Str::random(8)))
                    ->extraInputAttributes(['style' => 'text-transform:uppercase;font-family:monospace']),
                TextInput::make('description')->placeholder('Shown to admins only'),
                Select::make('type')->options(['percent' => 'Percentage (%)', 'fixed' => 'Fixed amount'])->default('percent')->required()->live(),
                TextInput::make('value')->numeric()->required()->minValue(0)
                    ->suffix(fn (Get $get) => $get('type') === 'percent' ? '%' : setting('general.currency_symbol')),
                TextInput::make('max_discount')->label('Maximum discount')->numeric()->visible(fn (Get $get) => $get('type') === 'percent')->helperText('Cap for percentage coupons (optional).'),
                Toggle::make('free_shipping')->label('Also gives free delivery')->inline(false),
            ]),
            Section::make('Conditions')->columns(2)->schema([
                TextInput::make('min_order')->label('Minimum order amount')->numeric(),
                TextInput::make('usage_limit')->label('Total usage limit')->numeric()->integer()->helperText('Empty = unlimited'),
                DateTimePicker::make('starts_at')->native(false),
                DateTimePicker::make('expires_at')->native(false),
                Toggle::make('is_active')->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->searchable()->copyable()->weight('bold')->fontFamily('mono')->description(fn (Coupon $r) => $r->description),
                TextColumn::make('value')->label('Discount')->formatStateUsing(fn (Coupon $r) => ($r->type === 'percent' ? (float) $r->value.'%' : money($r->value)).($r->free_shipping ? ' + free delivery' : '')),
                TextColumn::make('min_order')->label('Min. order')->formatStateUsing(fn ($state) => $state ? money($state) : '—'),
                TextColumn::make('used_count')->label('Used')->formatStateUsing(fn (Coupon $r) => $r->used_count.($r->usage_limit ? ' / '.$r->usage_limit : ''))->badge(),
                TextColumn::make('expires_at')->dateTime('d M Y')->placeholder('Never')->color(fn (Coupon $r) => $r->expires_at?->isPast() ? 'danger' : null),
                ToggleColumn::make('is_active')->label('Active'),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCoupons::route('/'),
            'create' => CreateCoupon::route('/create'),
            'edit' => EditCoupon::route('/{record}/edit'),
        ];
    }
}
