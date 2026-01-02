<?php

namespace App\Orchid\Screens\Export;

use App\Jobs\Export as ExportJob;
use App\Models\Export;
use App\Orchid\Layouts\Export\ExportEditLayout;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class ExportEditScreen extends Screen
{
    /**
     * @var Export
     */
    public $export;

    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(Export $export): iterable
    {
        return [
            'export' => $export,
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return $this->export->exists ? 'Edit Export' : 'Create Export';
    }

    /**
     * Display header description.
     */
    public function description(): ?string
    {
        return 'Export job details and configuration';
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
                ->type(\Orchid\Support\Color::SUCCESS()),

            Button::make(__('Retry'))
                ->icon('bs.arrow-clockwise')
                ->method('retry')
                ->canSee($this->export->exists && $this->export->status === 'failed')
                ->type(\Orchid\Support\Color::WARNING()),

            Button::make(__('Remove'))
                ->icon('bs.trash3')
                ->method('remove')
                ->canSee($this->export->exists)
                ->confirm(__('Are you sure you want to delete this export?')),
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
            Layout::block(ExportEditLayout::class)
                ->title(__('Export Information'))
                ->description(__('Configure the export job details and settings.'))
                ->commands(
                    Button::make(__('Save'))
                        ->type(\Orchid\Support\Color::BASIC())
                        ->icon('bs.check-circle')
                        ->canSee($this->export->exists)
                        ->method('save')
                ),
        ];
    }

    /**
     * Save the export and automatically queue it for processing.
     *
     * @param Request $request
     * @param Export $export
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function save(Request $request, Export $export)
    {
        $validated = $request->validate([
            'export.service' => 'required|string|max:255',
            'export.userId' => 'required|string|max:255',
            'export.videoId' => 'required|string|max:255',
            'export.videoAspectRatio' => 'required|string',
            'export.videoResolution' => 'required|string',
            'export.videoOutro' => 'boolean',
        ]);

        $export->fill($validated['export']);
        $export->status = 'pending';
        $export->save();

        // Automatically dispatch to 'exports' queue
        ExportJob::dispatch(
            $export->service,
            $export->userId,
            $export->videoId,
            $export->videoAspectRatio,
            $export->videoResolution,
            $export->videoOutro,
            $export->id
        )->onQueue('exports');

        Toast::success(__('Export queued for processing!'));

        return redirect()->route('platform.exports');
    }

    /**
     * Retry a failed export.
     *
     * @param Export $export
     * @return \Illuminate\Http\RedirectResponse
     */
    public function retry(Export $export)
    {
        if ($export->status !== 'failed') {
            Toast::warning(__('Only failed exports can be retried!'));
            return redirect()->route('platform.exports.edit', $export->id);
        }

        // Clear old file path and process output on retry
        $export->update([
            'status' => 'pending',
            'file_path' => null,
            'process_output' => null,
        ]);

        // Dispatch the export job again
        ExportJob::dispatch(
            $export->service,
            $export->userId,
            $export->videoId,
            $export->videoAspectRatio,
            $export->videoResolution,
            $export->videoOutro,
            $export->id
        )->onQueue('exports');

        Toast::success(__('Export queued for retry!'));

        return redirect()->route('platform.exports');
    }



    /**
     * Remove the export.
     *
     * @param Export $export
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function remove(Export $export)
    {
        $export->delete();

        Toast::info(__('Export was removed'));

        return redirect()->route('platform.exports');
    }
}
