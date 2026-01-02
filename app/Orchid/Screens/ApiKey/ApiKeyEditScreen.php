<?php

namespace App\Orchid\Screens\ApiKey;

use App\Models\ApiKey;
use App\Models\User;
use App\Orchid\Layouts\ApiKey\ApiKeyEditLayout;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Screen;
use Orchid\Support\Color;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class ApiKeyEditScreen extends Screen
{
    /**
     * @var ApiKey
     */
    public $apiKey;

    /**
     * @var string|null The newly generated key (only shown once)
     */
    public $generatedKey;

    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(ApiKey $apiKey): iterable
    {
        return [
            'apiKey' => $apiKey,
            'generatedKey' => session('generated_key'),
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return $this->apiKey->exists ? 'Edit API Key' : 'Create API Key';
    }

    /**
     * Display header description.
     */
    public function description(): ?string
    {
        return $this->apiKey->exists
            ? 'Update API key settings'
            : 'Generate a new API key for external integrations';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            Button::make(__('Save'))
                ->icon('bs.check-circle')
                ->method('save')
                ->type(Color::SUCCESS()),

            Button::make(__('Regenerate Key'))
                ->icon('bs.arrow-repeat')
                ->method('regenerate')
                ->canSee($this->apiKey->exists)
                ->confirm(__('This will invalidate the current key. Are you sure?')),

            Button::make(__('Remove'))
                ->icon('bs.trash3')
                ->method('remove')
                ->canSee($this->apiKey->exists)
                ->confirm(__('Are you sure you want to delete this API key?')),
        ];
    }

    /**
     * The screen's layout elements.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): iterable
    {
        $layouts = [];

        // Show the generated key alert if available
        if (session('generated_key')) {
            $layouts[] = Layout::view('orchid.api-key-alert', [
                'generatedKey' => session('generated_key'),
            ]);
        }

        $layouts[] = Layout::block(ApiKeyEditLayout::class)
            ->title(__('API Key Information'))
            ->description(__('Configure the API key details and settings.'))
            ->commands(
                Button::make(__('Save'))
                    ->type(Color::BASIC())
                    ->icon('bs.check-circle')
                    ->canSee($this->apiKey->exists)
                    ->method('save')
            );

        return $layouts;
    }

    /**
     * Save the API key.
     *
     * @param Request $request
     * @param ApiKey $apiKey
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function save(Request $request, ApiKey $apiKey)
    {
        $validated = $request->validate([
            'apiKey.name' => 'required|string|max:255',
            'apiKey.user_id' => 'required|exists:users,id',
            'apiKey.description' => 'nullable|string|max:1000',
            'apiKey.expires_at' => 'nullable|date|after:now',
            'apiKey.is_active' => 'boolean',
        ]);

        $apiKey->fill($validated['apiKey']);

        // Generate a new key if this is a new API key
        if (!$apiKey->exists) {
            $generatedKey = ApiKey::generateKey();
            $apiKey->key = $generatedKey;
            $apiKey->save();

            // Flash the generated key to show it once
            session()->flash('generated_key', $generatedKey);

            Toast::success(__('API key created successfully! Make sure to copy the key now - it won\'t be shown again.'));

            return redirect()->route('platform.api-keys.edit', $apiKey);
        }

        $apiKey->save();

        Toast::success(__('API key updated successfully!'));

        return redirect()->route('platform.api-keys');
    }

    /**
     * Regenerate the API key.
     *
     * @param ApiKey $apiKey
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function regenerate(ApiKey $apiKey)
    {
        $generatedKey = ApiKey::generateKey();
        $apiKey->update(['key' => $generatedKey]);

        session()->flash('generated_key', $generatedKey);

        Toast::warning(__('API key regenerated! Make sure to copy the new key - it won\'t be shown again.'));

        return redirect()->route('platform.api-keys.edit', $apiKey);
    }

    /**
     * Remove the API key.
     *
     * @param ApiKey $apiKey
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function remove(ApiKey $apiKey)
    {
        $apiKey->delete();

        Toast::info(__('API key was removed'));

        return redirect()->route('platform.api-keys');
    }
}
