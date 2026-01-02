<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Export;

use App\Models\ExportSetting;
use Orchid\Screen\Field;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Layouts\Rows;

class ExportEditLayout extends Rows
{
    /**
     * Define the fields for the export form.
     *
     * @return Field[]
     */
    protected function fields(): array
    {
        return [
            Input::make('export.service')
                ->title('Service')
                ->placeholder('Enter service name')
                ->help('The service platform (e.g., ft, local)')
                ->required(),

            Input::make('export.userId')
                ->title('User ID')
                ->placeholder('Enter user ID')
                ->help('The ID of the user who owns this export')
                ->required(),

            Input::make('export.videoId')
                ->title('Video ID')
                ->placeholder('Enter video ID')
                ->help('The ID of the video to export')
                ->required(),

            Select::make('export.videoAspectRatio')
                ->title('Aspect Ratio')
                ->options(ExportSetting::getAspectRatios())
                ->help('Select the video aspect ratio')
                ->required(),

            Select::make('export.videoResolution')
                ->title('Resolution')
                ->options(ExportSetting::getResolutions())
                ->help('Select the video resolution')
                ->required(),

            CheckBox::make('export.videoOutro')
                ->title('Include Outro')
                ->placeholder('Add outro to the video')
                ->help('Whether to include an outro at the end of the video')
                ->sendTrueOrFalse(),

            Input::make('export.status')
                ->title('Status')
                ->placeholder('Automatically managed')
                ->help('Status is automatically updated as the export processes')
                ->readonly()
                ->canSee(
                    $this->query->has('export') &&
                        $this->query->get('export') !== null &&
                        method_exists($this->query->get('export'), 'exists') &&
                        $this->query->get('export')->exists
                ),

            Input::make('export.file_path')
                ->title('File Path')
                ->placeholder('Available after completion')
                ->help('The path where the exported file is stored')
                ->readonly()
                ->canSee(
                    $this->query->has('export') &&
                        $this->query->get('export') !== null &&
                        method_exists($this->query->get('export'), 'exists') &&
                        $this->query->get('export')->exists
                ),

            Input::make('export.process_output')
                ->title('Process Output')
                ->placeholder('Command output will appear here')
                ->help('The output from the export process')
                ->readonly()
                ->type('textarea')
                ->canSee(
                    $this->query->has('export') &&
                        $this->query->get('export') !== null &&
                        method_exists($this->query->get('export'), 'exists') &&
                        $this->query->get('export')->exists
                ),
        ];
    }
}
