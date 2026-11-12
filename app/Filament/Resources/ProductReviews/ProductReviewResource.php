<?php

namespace App\Filament\Resources\ProductReviews;

use App\Filament\Resources\ProductReviews\Pages\EditProductReview;
use App\Filament\Resources\ProductReviews\Pages\ListProductReviews;
use App\Models\ProductReview;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use UnitEnum;

class ProductReviewResource extends Resource
{
    protected static ?string $model = ProductReview::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static string|UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?string $navigationLabel = 'Reviews';

    protected static ?int $navigationSort = 4;

    public static function getNavigationBadge(): ?string
    {
        $pending = ProductReview::where('is_approved', false)->count();

        return $pending ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                Select::make('product_id')->relationship('product', 'name')->searchable()->required()->columnSpanFull(),
                TextInput::make('name')->required(),
                TextInput::make('email')->email(),
                Select::make('rating')->options([5 => '★★★★★', 4 => '★★★★', 3 => '★★★', 2 => '★★', 1 => '★'])->required(),
                Toggle::make('is_verified')->label('Verified buyer')->inline(false),
                Textarea::make('comment')->rows(4)->columnSpanFull(),
                Toggle::make('is_approved')->label('Approved (visible on site)'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('product.name')->limit(36)->searchable()->url(fn (ProductReview $r) => $r->product?->url, true),
                TextColumn::make('name')->label('Customer')->searchable()->description(fn (ProductReview $r) => $r->is_verified ? 'Verified buyer' : null),
                TextColumn::make('rating')->formatStateUsing(fn ($state) => str_repeat('★', (int) $state))->color('warning')->sortable(),
                TextColumn::make('comment')->limit(60)->wrap()->tooltip(fn (ProductReview $r) => $r->comment),
                ToggleColumn::make('is_approved')->label('Approved'),
                TextColumn::make('created_at')->since()->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_approved')->label('Approved'),
                SelectFilter::make('rating')->options([5 => '5 stars', 4 => '4 stars', 3 => '3 stars', 2 => '2 stars', 1 => '1 star']),
            ])
            ->recordActions([
                Action::make('approve')->icon('heroicon-o-check')->color('success')->visible(fn (ProductReview $r) => ! $r->is_approved)
                    ->action(fn (ProductReview $r) => $r->update(['is_approved' => true])),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('approve')->label('Approve selected')->icon('heroicon-o-check')
                        ->action(fn (Collection $records) => $records->each->update(['is_approved' => true]))->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductReviews::route('/'),
            'edit' => EditProductReview::route('/{record}/edit'),
        ];
    }
}
