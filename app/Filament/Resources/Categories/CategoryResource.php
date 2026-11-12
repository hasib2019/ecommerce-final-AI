<?php

namespace App\Filament\Resources\Categories;

use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Filament\Support\Fields;
use App\Models\Category;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Group::make()->columnSpan(['lg' => 2])->schema([
                Section::make()->schema([
                    Fields::nameWithSlug('name', 'Category name'),
                    Fields::slug('category'),
                    Select::make('parent_id')
                        ->label('Parent category')
                        ->relationship('parent', 'name', fn ($query, ?Category $record) => $query->when($record, fn ($q) => $q->whereKeyNot($record->id)))
                        ->searchable()
                        ->preload()
                        ->placeholder('— Top level —'),
                    Textarea::make('description')->rows(4)->helperText('Shown at the bottom of the category page – great for SEO (100–300 words).'),
                ]),
                Fields::seo('name', 'description'),
            ]),
            Group::make()->schema([
                Section::make('Visibility')->schema([
                    Toggle::make('is_active')->label('Active')->default(true),
                    Toggle::make('is_featured')->label('Featured on home page'),
                    Toggle::make('show_in_menu')->label('Show in menus')->default(true),
                ]),
                Section::make('Images')->schema([
                    Fields::image('image', 'categories', 'Icon / thumbnail')->helperText('Square image used in menus and category grids.'),
                    Fields::image('banner', 'categories', 'Banner')->helperText('Wide image shown at the top of the category page (1600×320).'),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                ImageColumn::make('image')->label('')->disk('public')->circular()->imageSize(40),
                TextColumn::make('name')->searchable()->sortable()->weight('medium')
                    ->description(fn (Category $r) => $r->parent ? 'in '.$r->parent->name : 'Top level'),
                TextColumn::make('products_count')->counts('products')->label('Products')->sortable()->badge(),
                ToggleColumn::make('is_featured')->label('Featured'),
                ToggleColumn::make('show_in_menu')->label('In menu'),
                ToggleColumn::make('is_active')->label('Active'),
            ])
            ->modifyQueryUsing(fn ($query) => $query->with('parent:id,name'))
            ->filters([
                SelectFilter::make('parent_id')->label('Parent')->relationship('parent', 'name'),
            ])
            ->recordActions([
                Action::make('view')->label('')->icon('heroicon-o-arrow-top-right-on-square')->color('gray')->url(fn (Category $r) => $r->url, true),
                EditAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'edit' => EditCategory::route('/{record}/edit'),
        ];
    }
}
