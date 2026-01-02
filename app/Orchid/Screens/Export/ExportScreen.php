<?php

namespace App\Orchid\Screens\Export;

use App\Models\Export;
use App\Orchid\Layouts\Export\ExportListLayout;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class ExportScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [
            'exports' => Export::latest()->paginate(),
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Export Management';
    }

    public function description(): ?string
    {
        return 'Manage the server\'s queued exports';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            Link::make(__('Add'))
                ->icon('bs.plus-circle')
                ->route('platform.exports.create'),
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
            ExportListLayout::class,
            Layout::modal('viewLogModal', [
                Layout::rows([
                    TextArea::make('log_output')
                        ->disabled()
                        ->rows(20),
                ]),
            ])
                ->title('Export Process Log')
                ->size('lg')
                ->withoutApplyButton()
                ->closeButton('Close')
                ->deferred('loadExportLog'),
        ];
    }

    /**
     * Load export log data for the modal.
     *
     * @param int $id
     *
     * @return array
     */
    public function loadExportLog(int $id): array
    {
        $export = Export::findOrFail($id);

        return [
            'log_output' => $export->process_output ?? 'No output available',
        ];
    }

    /**
     * Remove export.
     *
     * @param Request $request
     *
     * @return void
     */
    public function remove(Request $request): void
    {
        Export::findOrFail($request->get('id'))->delete();

        Toast::info(__('Export was removed'));
    }
}
