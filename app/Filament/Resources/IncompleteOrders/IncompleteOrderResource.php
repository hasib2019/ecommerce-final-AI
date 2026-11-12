<?php

namespace App\Filament\Resources\IncompleteOrders;

use App\Filament\Resources\IncompleteOrders\Pages\EditIncompleteOrder;
use App\Filament\Resources\IncompleteOrders\Pages\ListIncompleteOrders;
use App\Models\IncompleteOrder;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use UnitEnum;

/** Checkouts where the customer entered a phone number but never placed the order – call them back! */
class IncompleteOrderResource extends Resource
{
    protected static ?string $model = IncompleteOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoneArrowUpRight;

    protected static string|UnitEnum|null $navigationGroup = 'Sales';

    protected static ?string $navigationLabel = 'Abandoned checkouts';

    protected static ?int $navigationSort = 2;

    public const STATUSES = ['new' => 'New', 'called' => 'Called', 'no_answer' => 'No answer', 'not_interested' => 'Not interested', 'converted' => 'Converted'];

    public static function getNavigationBadge(): ?string
    {
        $n = IncompleteOrder::where('is_converted', false)->where('status', 'new')->where('created_at', '>=', now()->subDays(7))->count();

        return $n ? (string) $n : null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                Text::make(fn (IncompleteOrder $record) => new HtmlString(
                    '<strong>'.e($record->name ?: 'Unknown').'</strong> · '.e($record->phone).'<br>'.e($record->address).'<br><br>'
                    .collect($record->cart)->map(fn ($i) => '• '.e($i['product']).(! empty($i['variant']) ? ' ('.e($i['variant']).')' : '').' × '.$i['qty'].' – '.money($i['price'] * $i['qty']))->implode('<br>')
                    .'<br><br><strong>Total: '.money($record->total).'</strong>'
                )),
                Select::make('status')->options(self::STATUSES)->required(),
                Textarea::make('admin_note')->label('Note')->rows(3),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->description('Customers who started checkout and typed their phone number but did not finish. A quick call recovers many of these sales.')
            ->columns([
                TextColumn::make('name')->placeholder('—')->searchable()->description(fn (IncompleteOrder $r) => $r->phone),
                TextColumn::make('cart')->label('Cart')->formatStateUsing(fn (IncompleteOrder $r) => collect($r->cart)->map(fn ($i) => $i['product'].' ×'.$i['qty'])->implode(', '))->limit(60)->wrap(),
                TextColumn::make('total')->formatStateUsing(fn ($state) => money($state))->sortable(),
                SelectColumn::make('status')->options(self::STATUSES)->selectablePlaceholder(false),
                TextColumn::make('updated_at')->label('Last activity')->since()->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_converted')->label('Converted to order')->default(false),
            ])
            ->recordActions([
                Action::make('call')->icon('heroicon-o-phone')->color('success')->url(fn (IncompleteOrder $r) => 'tel:'.$r->phone),
                Action::make('whatsapp')->icon('heroicon-o-chat-bubble-left-right')->color('gray')
                    ->url(fn (IncompleteOrder $r) => 'https://wa.me/88'.ltrim(preg_replace('/\D/', '', (string) $r->phone), '88').'?text='.rawurlencode('Hi'.($r->name ? ' '.$r->name : '').'! We noticed you were ordering from '.setting('general.site_name').'. Can we help you complete your order?'), true),
                EditAction::make()->label('Note'),
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
            'index' => ListIncompleteOrders::route('/'),
            'edit' => EditIncompleteOrder::route('/{record}/edit'),
        ];
    }
}
