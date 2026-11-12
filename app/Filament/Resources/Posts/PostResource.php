<?php

namespace App\Filament\Resources\Posts;

use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Filament\Support\Fields;
use App\Models\Post;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
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
use Filament\Tables\Table;
use UnitEnum;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static string|UnitEnum|null $navigationGroup = 'Storefront';

    protected static ?string $navigationLabel = 'Blog posts';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Group::make()->columnSpan(['lg' => 2])->schema([
                Section::make()->schema([
                    Fields::nameWithSlug('title', 'Title'),
                    Fields::slug('blog'),
                    Textarea::make('excerpt')->rows(2)->maxLength(500)->helperText('Short summary shown in listings.'),
                    RichEditor::make('content')->fileAttachmentsDisk('public')->fileAttachmentsDirectory('blog'),
                ]),
                Fields::seo('title', 'excerpt'),
            ]),
            Group::make()->schema([
                Section::make('Publishing')->schema([
                    Toggle::make('is_published')->label('Published')->default(true),
                    DateTimePicker::make('published_at')->native(false)->default(now())->helperText('Schedule by choosing a future date.'),
                    Hidden::make('user_id')->default(fn () => auth()->id()),
                ]),
                Section::make('Cover image')->schema([Fields::image('image', 'blog')->hiddenLabel()]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('published_at', 'desc')
            ->columns([
                ImageColumn::make('image')->label('')->disk('public')->imageHeight(40),
                TextColumn::make('title')->searchable()->weight('medium')->limit(60),
                TextColumn::make('published_at')->dateTime('d M Y')->sortable(),
                ToggleColumn::make('is_published')->label('Published'),
            ])
            ->recordActions([
                Action::make('view')->label('')->icon('heroicon-o-arrow-top-right-on-square')->color('gray')->url(fn (Post $r) => $r->url, true),
                EditAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPosts::route('/'),
            'create' => CreatePost::route('/create'),
            'edit' => EditPost::route('/{record}/edit'),
        ];
    }
}
