<?php

namespace App\Filament\Resources\ContactMessages;

use App\Filament\Resources\ContactMessages\Pages\ListContactMessages;
use App\Filament\Resources\ContactMessages\Pages\ViewContactMessage;
use App\Models\ContactMessage;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class ContactMessageResource extends Resource
{
    protected static ?string $model = ContactMessage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInbox;

    protected static string|UnitEnum|null $navigationGroup = 'Customers';

    protected static ?string $navigationLabel = 'Messages';

    public static function getNavigationBadge(): ?string
    {
        $n = ContactMessage::where('is_read', false)->count();

        return $n ? (string) $n : null;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(3)->schema([
                TextEntry::make('name'),
                TextEntry::make('phone')->placeholder('—')->url(fn ($record) => $record->phone ? 'tel:'.$record->phone : null),
                TextEntry::make('email')->placeholder('—')->url(fn ($record) => $record->email ? 'mailto:'.$record->email : null),
                TextEntry::make('subject')->placeholder('—')->columnSpanFull(),
                TextEntry::make('message')->columnSpanFull()->prose(),
                TextEntry::make('created_at')->dateTime(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                IconColumn::make('is_read')->label('')->boolean()->trueIcon('heroicon-o-envelope-open')->falseIcon('heroicon-s-envelope')->falseColor('primary'),
                TextColumn::make('name')->searchable()->weight(fn (ContactMessage $r) => $r->is_read ? null : 'bold')->description(fn (ContactMessage $r) => $r->phone ?: $r->email),
                TextColumn::make('subject')->placeholder('—')->limit(40),
                TextColumn::make('message')->limit(70),
                TextColumn::make('created_at')->since()->sortable(),
            ])
            ->recordActions([
                ViewAction::make()->after(fn (ContactMessage $r) => $r->update(['is_read' => true])),
                Action::make('read')->label('Mark read')->icon('heroicon-o-check')->visible(fn (ContactMessage $r) => ! $r->is_read)->action(fn (ContactMessage $r) => $r->update(['is_read' => true])),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContactMessages::route('/'),
            'view' => ViewContactMessage::route('/{record}'),
        ];
    }
}
