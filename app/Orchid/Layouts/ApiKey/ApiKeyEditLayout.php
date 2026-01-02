<?php

namespace App\Orchid\Layouts\ApiKey;

use App\Models\User;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Layouts\Rows;

class ApiKeyEditLayout extends Rows
{
    /**
     * The screen's layout elements.
     *
     * @return \Orchid\Screen\Field[]
     */
    protected function fields(): iterable
    {
        return [
            Input::make('apiKey.name')
                ->type('text')
                ->max(255)
                ->required()
                ->title(__('Name'))
                ->placeholder(__('My API Key'))
                ->help(__('A friendly name to identify this API key.')),

            Relation::make('apiKey.user_id')
                ->fromModel(User::class, 'name')
                ->required()
                ->title(__('Owner'))
                ->help(__('The user who owns this API key.')),

            TextArea::make('apiKey.description')
                ->rows(3)
                ->max(1000)
                ->title(__('Description'))
                ->placeholder(__('What is this API key used for?'))
                ->help(__('Optional description for this API key.')),

            DateTimer::make('apiKey.expires_at')
                ->title(__('Expiration Date'))
                ->placeholder(__('Select expiration date'))
                ->help(__('Leave empty for no expiration. The key will stop working after this date.')),

            CheckBox::make('apiKey.is_active')
                ->sendTrueOrFalse()
                ->title(__('Active'))
                ->placeholder(__('API key is active'))
                ->help(__('Inactive keys cannot be used for authentication.')),
        ];
    }
}
