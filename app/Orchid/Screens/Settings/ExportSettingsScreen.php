<?php

namespace App\Orchid\Screens\Settings;

use App\Models\ExportSetting;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Password;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Support\Color;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class ExportSettingsScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        $aspectRatios = ExportSetting::getAspectRatios();
        $resolutions = ExportSetting::getResolutions();
        $cliSettings = ExportSetting::getCliSettings();

        return [
            'aspect_ratios' => $this->formatForTextArea($aspectRatios),
            'resolutions' => $this->formatForTextArea($resolutions),
            'cli' => $cliSettings,
            'purge_after_days' => ExportSetting::getPurgeAfterDays(),
            'show_homepage' => ExportSetting::get('show_homepage', true),
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Export Settings';
    }

    /**
     * Display header description.
     */
    public function description(): ?string
    {
        return 'Configure export options and GoExport CLI parameters';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            Button::make(__('Save Settings'))
                ->icon('bs.check-circle')
                ->method('save')
                ->type(Color::SUCCESS()),

            Button::make(__('Reset to Defaults'))
                ->icon('bs.arrow-counterclockwise')
                ->method('reset')
                ->confirm(__('Are you sure you want to reset to default settings?')),
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
            Layout::block([
                Layout::rows([
                    TextArea::make('aspect_ratios')
                        ->title(__('Aspect Ratios'))
                        ->rows(8)
                        ->help(__('Enter one aspect ratio per line in format: key|Label (e.g., 16:9|16:9 (Widescreen))')),
                ]),
            ])
                ->title(__('Video Aspect Ratios'))
                ->description(__('Configure the available aspect ratios for video exports.')),

            Layout::block([
                Layout::rows([
                    TextArea::make('resolutions')
                        ->title(__('Video Resolutions'))
                        ->rows(8)
                        ->help(__('Enter one resolution per line in format: key|Label (e.g., 1080p|1080p (Full HD))')),
                ]),
            ])
                ->title(__('Video Resolutions'))
                ->description(__('Configure the available resolutions for video exports.')),

            Layout::block([
                Layout::rows([
                    Input::make('cli.obs_websocket_address')
                        ->title(__('OBS WebSocket Address'))
                        ->placeholder('localhost')
                        ->help(__('The address of the OBS WebSocket server.')),

                    Input::make('cli.obs_websocket_port')
                        ->title(__('OBS WebSocket Port'))
                        ->type('number')
                        ->placeholder('4455')
                        ->help(__('The port of the OBS WebSocket server.')),

                    Password::make('cli.obs_websocket_password')
                        ->title(__('OBS WebSocket Password'))
                        ->placeholder('Leave empty if unchanged')
                        ->help(__('The password for the OBS WebSocket server.')),

                    Input::make('cli.obs_fps')
                        ->title(__('OBS FPS'))
                        ->type('number')
                        ->placeholder('30')
                        ->help(__('The FPS setting for OBS recording.')),

                    CheckBox::make('cli.obs_no_overwrite')
                        ->title(__('OBS Options'))
                        ->placeholder(__('Prevent scene overwriting'))
                        ->sendTrueOrFalse()
                        ->help(__('When enabled, prevents GoExport from overwriting existing OBS scenes.')),

                    CheckBox::make('cli.obs_required')
                        ->placeholder(__('Require OBS connection'))
                        ->sendTrueOrFalse()
                        ->help(__('When enabled, exports will fail if OBS is not connected.')),
                ]),
            ])
                ->title(__('OBS WebSocket Settings'))
                ->description(__('Configure the OBS WebSocket connection for recording.')),

            Layout::block([
                Layout::rows([
                    Input::make('cli.load_timeout')
                        ->title(__('Load Timeout (minutes)'))
                        ->type('number')
                        ->placeholder('30')
                        ->help(__('Timeout in minutes to wait for video to load. Set to 0 to disable.')),

                    Input::make('cli.video_timeout')
                        ->title(__('Video Timeout (minutes)'))
                        ->type('number')
                        ->placeholder('0')
                        ->help(__('Timeout in minutes to wait for video to finish after loading. Set to 0 to disable.')),

                    CheckBox::make('cli.force_outro')
                        ->title(__('Outro Settings'))
                        ->placeholder(__('Force outro on all exports'))
                        ->sendTrueOrFalse()
                        ->help(__('When enabled, the outro will always be added regardless of the export request.')),
                ]),
            ])
                ->title(__('Timeout & Outro Settings'))
                ->description(__('Configure timeouts and outro behavior for exports.')),

            Layout::block([
                Layout::rows([
                    Input::make('purge_after_days')
                        ->title(__('Purge Old Exports (Minutes)'))
                        ->type('number')
                        ->placeholder('30')
                        ->help(__('Automatically delete export files older than this many minutes. Set to 0 to disable.')),
                ]),
            ])
                ->title(__('Storage Maintenance'))
                ->description(__('Configure automatic cleanup of old export files.')),

            Layout::block([
                Layout::rows([
                    CheckBox::make('show_homepage')
                        ->title(__('Homepage Settings'))
                        ->placeholder(__('Display homepage with video grid'))
                        ->sendTrueOrFalse()
                        ->help(__('When enabled, the homepage displays a paginated grid of exported videos. When disabled, visitors will see a 404 error.')),
                ]),
            ])
                ->title(__('Homepage'))
                ->description(__('Configure the public homepage display settings.')),
        ];
    }

    /**
     * Save the settings.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function save(Request $request)
    {
        $aspectRatios = $this->parseTextArea($request->input('aspect_ratios', ''));
        $resolutions = $this->parseTextArea($request->input('resolutions', ''));

        if (empty($aspectRatios)) {
            Toast::error(__('At least one aspect ratio is required.'));
            return redirect()->back();
        }

        if (empty($resolutions)) {
            Toast::error(__('At least one resolution is required.'));
            return redirect()->back();
        }

        ExportSetting::set('aspect_ratios', $aspectRatios, 'Available video aspect ratios');
        ExportSetting::set('resolutions', $resolutions, 'Available video resolutions');

        // Save CLI settings
        $cli = $request->input('cli', []);
        ExportSetting::set('obs_websocket_address', $cli['obs_websocket_address'] ?? '', 'OBS WebSocket address');
        ExportSetting::set('obs_websocket_port', $cli['obs_websocket_port'] ?? '', 'OBS WebSocket port');
        ExportSetting::set('purge_after_days', (int) $request->input('purge_after_days', 30), 'Purge old exports after days');

        // Only update password if provided
        if (!empty($cli['obs_websocket_password'])) {
            ExportSetting::set('obs_websocket_password', $cli['obs_websocket_password'], 'OBS WebSocket password');
        }

        ExportSetting::set('obs_fps', $cli['obs_fps'] ?? '', 'OBS FPS');
        ExportSetting::set('obs_no_overwrite', (bool) ($cli['obs_no_overwrite'] ?? false), 'Prevent OBS scene overwriting');
        ExportSetting::set('obs_required', (bool) ($cli['obs_required'] ?? false), 'Require OBS connection');
        ExportSetting::set('load_timeout', (int) ($cli['load_timeout'] ?? 30), 'Load timeout in minutes');
        ExportSetting::set('video_timeout', (int) ($cli['video_timeout'] ?? 0), 'Video timeout in minutes');
        ExportSetting::set('force_outro', (bool) ($cli['force_outro'] ?? false), 'Force outro on all exports');
        ExportSetting::set('show_homepage', (bool) ($request->input('show_homepage', true)), 'Display homepage with video grid');

        ExportSetting::clearCache();

        Toast::success(__('Export settings saved successfully!'));

        return redirect()->route('platform.settings.exports');
    }

    /**
     * Reset settings to defaults.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reset()
    {
        ExportSetting::set('aspect_ratios', [
            '4:3' => '4:3 (Standard)',
            '16:9' => '16:9 (Widescreen)',
            '14:9' => '14:9 (Classic)',
            '9:16' => '9:16 (Vertical)',
        ], 'Available video aspect ratios');

        ExportSetting::set('resolutions', [
            '240p' => '240p (Low)',
            '360p' => '360p (SD)',
            '420p' => '420p',
            '480p' => '480p',
            '720p' => '720p (HD)',
            '1080p' => '1080p (Full HD)',
            '1440p' => '1440p (2K)',
            '2k' => '2K',
            '4k' => '4K (Ultra HD)',
            '5k' => '5K',
            '8k' => '8K',
        ], 'Available video resolutions');

        // Reset CLI settings
        ExportSetting::set('obs_websocket_address', '', 'OBS WebSocket address');
        ExportSetting::set('obs_websocket_port', '', 'OBS WebSocket port');
        ExportSetting::set('obs_websocket_password', '', 'OBS WebSocket password');
        ExportSetting::set('obs_fps', '', 'OBS FPS');
        ExportSetting::set('obs_no_overwrite', false, 'Prevent OBS scene overwriting');
        ExportSetting::set('obs_required', false, 'Require OBS connection');
        ExportSetting::set('load_timeout', 30, 'Load timeout in minutes');
        ExportSetting::set('video_timeout', 0, 'Video timeout in minutes');
        ExportSetting::set('purge_after_days', 30, 'Purge old exports after days');
        ExportSetting::set('force_outro', false, 'Force outro on all exports');
        ExportSetting::set('show_homepage', true, 'Display homepage with video grid');

        ExportSetting::clearCache();

        Toast::success(__('Export settings reset to defaults!'));

        return redirect()->route('platform.settings.exports');
    }

    /**
     * Format array for textarea display.
     *
     * @param array $data
     * @return string
     */
    protected function formatForTextArea(array $data): string
    {
        $lines = [];
        foreach ($data as $key => $label) {
            $lines[] = "{$key}|{$label}";
        }
        return implode("\n", $lines);
    }

    /**
     * Parse textarea input to array.
     *
     * @param string $text
     * @return array
     */
    protected function parseTextArea(string $text): array
    {
        $result = [];
        $lines = array_filter(array_map('trim', explode("\n", $text)));

        foreach ($lines as $line) {
            if (str_contains($line, '|')) {
                [$key, $label] = explode('|', $line, 2);
                $key = trim($key);
                $label = trim($label);
                if ($key && $label) {
                    $result[$key] = $label;
                }
            }
        }

        return $result;
    }
}
