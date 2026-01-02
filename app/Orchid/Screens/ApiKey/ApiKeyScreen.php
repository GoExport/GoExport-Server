<?php

namespace App\Orchid\Screens\ApiKey;

use App\Models\ApiKey;
use App\Orchid\Layouts\ApiKey\ApiKeyListLayout;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Toast;

class ApiKeyScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [
            'apiKeys' => ApiKey::with('user')->latest()->paginate(),
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'API Keys';
    }

    /**
     * Display header description.
     */
    public function description(): ?string
    {
        return 'Manage API keys for external integrations';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            Link::make(__('Create API Key'))
                ->icon('bs.plus-circle')
                ->route('platform.api-keys.create'),
        ];
    }

    /**
     * The screen's layout elements.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): iterable
    {
        return [
            ApiKeyListLayout::class,
        ];
    }

    /**
     * Toggle API key active status.
     *
     * @param Request $request
     * @return void
     */
    public function toggle(Request $request): void
    {
        $apiKey = ApiKey::findOrFail($request->get('id'));
        $apiKey->update(['is_active' => !$apiKey->is_active]);

        Toast::info(__('API key status updated'));
    }

    /**
     * Remove API key.
     *
     * @param Request $request
     * @return void
     */
    public function remove(Request $request): void
    {
        ApiKey::findOrFail($request->get('id'))->delete();

        Toast::info(__('API key was removed'));
    }
}
