<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    public function getTitle(): string
    {
        return 'Order #'.$this->record->order_number;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('updateStatus')
                ->label('Update status')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->fillForm(fn (Order $record) => ['status' => $record->status])
                ->schema([
                    Select::make('status')->options(OrderStatus::class)->required()->native(false),
                    TextInput::make('note')->label('Note (optional)')->placeholder('e.g. Handed to Pathao, tracking #…'),
                ])
                ->action(function (Order $record, array $data) {
                    $record->update(['status' => $data['status']]);
                    if (filled($data['note'] ?? null)) {
                        $record->histories()->latest('id')->first()?->update(['note' => $data['note']]);
                    }
                    Notification::make()->title('Status updated to '.$record->status->getLabel())->success()->send();
                    $this->refreshFormData(['status']);
                }),
            Action::make('markPaid')
                ->label('Mark as paid')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->visible(fn (Order $record) => $record->payment_status !== PaymentStatus::Paid)
                ->requiresConfirmation()
                ->action(fn (Order $record) => $record->update(['payment_status' => PaymentStatus::Paid])),
            Action::make('invoice')
                ->label('Invoice')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn (Order $record) => route('admin.invoice', $record), shouldOpenInNewTab: true),
            Action::make('whatsapp')
                ->label('WhatsApp')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('gray')
                ->url(fn (Order $r) => 'https://wa.me/88'.ltrim(preg_replace('/\D/', '', $r->customer_phone), '88').'?text='.rawurlencode("Hello {$r->customer_name}, your order #{$r->order_number} from ".setting('general.site_name').' is '.strtolower($r->status->getLabel()).'.'), true),
            EditAction::make()->color('gray'),
        ];
    }
}
