<?php

namespace App\Orchid\Layouts\ApiKey;

use App\Models\ApiKey;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class ApiKeyListLayout extends Table
{
    /**
     * Data source.
     *
     * @var string
     */
    protected $target = 'apiKeys';

    /**
     * Get the table columns.
     *
     * @return TD[]
     */
    protected function columns(): iterable
    {
        return [
            TD::make('name', __('Name'))
                ->sort()
                ->filter(Input::make())
                ->render(fn(ApiKey $apiKey) => Link::make($apiKey->name)
                    ->route('platform.api-keys.edit', $apiKey)),

            TD::make('masked_key', __('API Key'))
                ->render(fn(ApiKey $apiKey) => '<code>' . $apiKey->masked_key . '</code>'),

            TD::make('user_id', __('Owner'))
                ->render(fn(ApiKey $apiKey) => $apiKey->user?->name ?? 'Unknown'),

            TD::make('is_active', __('Status'))
                ->render(fn(ApiKey $apiKey) => $apiKey->is_active
                    ? '<span class="badge bg-success">Active</span>'
                    : '<span class="badge bg-secondary">Inactive</span>'),

            TD::make('last_used_at', __('Last Used'))
                ->sort()
                ->render(fn(ApiKey $apiKey) => $apiKey->last_used_at?->diffForHumans() ?? 'Never'),

            TD::make('expires_at', __('Expires'))
                ->sort()
                ->render(function (ApiKey $apiKey) {
                    if (!$apiKey->expires_at) {
                        return '<span class="text-muted">Never</span>';
                    }

                    if ($apiKey->expires_at->isPast()) {
                        return '<span class="text-danger">' . $apiKey->expires_at->format('M d, Y') . ' (Expired)</span>';
                    }

                    return $apiKey->expires_at->format('M d, Y');
                }),

            TD::make('created_at', __('Created'))
                ->sort()
                ->render(fn(ApiKey $apiKey) => $apiKey->created_at->format('M d, Y')),

            TD::make(__('Actions'))
                ->align(TD::ALIGN_CENTER)
                ->width('100px')
                ->render(fn(ApiKey $apiKey) => DropDown::make()
                    ->icon('bs.three-dots-vertical')
                    ->list([
                        Link::make(__('Edit'))
                            ->route('platform.api-keys.edit', $apiKey)
                            ->icon('bs.pencil'),

                        Button::make(__($apiKey->is_active ? 'Deactivate' : 'Activate'))
                            ->method('toggle')
                            ->icon($apiKey->is_active ? 'bs.x-circle' : 'bs.check-circle')
                            ->confirm(__($apiKey->is_active
                                ? 'Are you sure you want to deactivate this API key?'
                                : 'Are you sure you want to activate this API key?'))
                            ->parameters(['id' => $apiKey->id]),

                        Button::make(__('Delete'))
                            ->method('remove')
                            ->confirm(__('Are you sure you want to delete this API key?'))
                            ->icon('bs.trash3')
                            ->parameters(['id' => $apiKey->id]),
                    ])),
        ];
    }
}
