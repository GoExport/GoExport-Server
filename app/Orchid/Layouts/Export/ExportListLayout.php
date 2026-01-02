<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Export;

use App\Models\Export;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Components\Cells\DateTimeSplit;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class ExportListLayout extends Table
{
    /**
     * @var string
     */
    protected $target = 'exports';

    /**
     * @return TD[]
     */
    protected function columns(): array
    {
        return [
            TD::make('id', 'ID')
                ->sort()
                ->cantHide(),

            TD::make('service', 'Service')
                ->sort()
                ->cantHide(),

            TD::make('userId', 'User ID')
                ->sort(),

            TD::make('videoId', 'Video ID')
                ->sort(),

            TD::make('videoAspectRatio', 'Aspect Ratio')
                ->sort(),

            TD::make('videoResolution', 'Resolution')
                ->sort(),

            TD::make('videoOutro', 'Outro')
                ->render(fn(Export $export) => $export->videoOutro ? 'Yes' : 'No'),

            TD::make('status', 'Status')
                ->sort()
                ->render(fn(Export $export) => ucfirst($export->status ?? 'pending')),

            TD::make('created_at', 'Created')
                ->usingComponent(DateTimeSplit::class)
                ->align(TD::ALIGN_RIGHT)
                ->sort(),

            TD::make('updated_at', 'Last Updated')
                ->usingComponent(DateTimeSplit::class)
                ->align(TD::ALIGN_RIGHT)
                ->defaultHidden()
                ->sort(),

            TD::make('process_output', 'Process Output')
                ->defaultHidden()
                ->render(fn(Export $export) => substr($export->process_output ?? '', 0, 50) . (strlen($export->process_output ?? '') > 50 ? '...' : '')),

            TD::make('Actions')
                ->align(TD::ALIGN_CENTER)
                ->width('100px')
                ->render(fn(Export $export) => DropDown::make()
                    ->icon('bs.three-dots-vertical')
                    ->list([
                        Link::make('View')
                            ->route('platform.exports.edit', $export->id)
                            ->icon('bs.eye'),

                        Link::make('Edit')
                            ->route('platform.exports.edit', $export->id)
                            ->icon('bs.pencil'),

                        ModalToggle::make('View Log')
                            ->icon('bs.terminal')
                            ->modal('viewLogModal')
                            ->modalTitle('Export Log')
                            ->modal('viewLogModal', [
                                'id' => $export->id,
                            ]),

                        Button::make('Retry')
                            ->icon('bs.arrow-clockwise')
                            ->canSee($export->status === 'failed')
                            ->confirm('Are you sure you want to retry this export?')
                            ->method('retry', [
                                'id' => $export->id,
                            ]),

                        Button::make('Delete')
                            ->icon('bs.trash3')
                            ->confirm('Are you sure you want to delete this export job?')
                            ->method('remove', [
                                'id' => $export->id,
                            ]),
                    ])),
        ];
    }
}
