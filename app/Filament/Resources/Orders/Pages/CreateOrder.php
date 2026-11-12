<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected static ?string $title = 'New manual order';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['customer_phone'] = preg_replace('/^\+?88/', '', preg_replace('/[\s\-]/', '', (string) $data['customer_phone']));

        return $data;
    }

    /** Manual (phone / Facebook) orders take stock out just like web orders. */
    protected function afterCreate(): void
    {
        $order = $this->record->refresh();
        $order->subtotal = $order->items()->sum('total');
        $order->total = max(0, $order->subtotal - $order->discount) + $order->shipping_cost;
        $order->saveQuietly();

        if (! $order->status->releasesStock()) {
            $order->adjustStock(-1);
        }
    }

    protected function getRedirectUrl(): string
    {
        return OrderResource::getUrl('view', ['record' => $this->record]);
    }
}
