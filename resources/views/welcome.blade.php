<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config("app.name") }}</title>

    <!-- Add tailwind css from laravel -->
    @vite('resources/css/app.css')
</head>

<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Header -->
        <div class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <h1 class="text-4xl font-bold text-gray-900">Welcome to {{ config("app.name") }}!</h1>
                <p class="mt-2 text-gray-600">Browse and download your exported videos</p>
            </div>
        </div>

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            @if($exports->count() > 0)
            <!-- Grid of Videos -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach($exports as $export)
                <a href="{{ $export->file_path }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="group relative block bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300">
                    <!-- Video Preview Placeholder -->
                    <div class="aspect-video bg-gradient-to-br from-gray-200 to-gray-300 flex items-center justify-center relative overflow-hidden">
                        <!-- Play Icon -->
                        <svg class="w-16 h-16 text-gray-600 group-hover:text-gray-700 transition-colors" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"></path>
                        </svg>
                    </div>

                    <!-- Video Info -->
                    <div class="p-4">
                        <h3 class="font-semibold text-gray-900 text-sm truncate group-hover:text-blue-600 transition-colors">
                            {{ $export->service ?? 'Video Export' }}
                        </h3>

                        <div class="mt-2 space-y-1 text-xs text-gray-600">
                            @if($export->videoResolution)
                            <p><span class="font-medium">Resolution:</span> {{ $export->videoResolution }}</p>
                            @endif

                            @if($export->videoAspectRatio)
                            <p><span class="font-medium">Aspect Ratio:</span> {{ $export->videoAspectRatio }}</p>
                            @endif

                            @if($export->status)
                            <p>
                                <span class="font-medium">Status:</span>
                                <span class="inline-block px-2 py-1 rounded-full text-xs font-semibold
                                                @if($export->status === 'completed' || $export->status === 'success')
                                                    bg-green-100 text-green-800
                                                @elseif($export->status === 'processing')
                                                    bg-blue-100 text-blue-800
                                                @elseif($export->status === 'failed' || $export->status === 'error')
                                                    bg-red-100 text-red-800
                                                @else
                                                    bg-gray-100 text-gray-800
                                                @endif
                                            ">
                                    {{ ucfirst($export->status) }}
                                </span>
                            </p>
                            @endif

                            <p class="pt-2 border-t border-gray-200 text-gray-500">
                                {{ $export->created_at?->format('M d, Y') ?? 'Unknown date' }}
                            </p>
                        </div>
                    </div>

                    <!-- Hover Overlay -->
                    <div class="absolute inset-0 bg-black opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
                </a>
                @endforeach
            </div>

            <!-- Pagination -->
            @if($exports->hasPages())
            <div class="mt-12">
                <nav class="flex items-center justify-between border-t border-gray-200 px-0 py-6">
                    <div class="flex flex-1 justify-between sm:hidden">
                        @if($exports->onFirstPage())
                        <span class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-500 cursor-not-allowed">
                            Previous
                        </span>
                        @else
                        <a href="{{ $exports->previousPageUrl() }}" class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Previous
                        </a>
                        @endif

                        @if($exports->hasMorePages())
                        <a href="{{ $exports->nextPageUrl() }}" class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Next
                        </a>
                        @else
                        <span class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-500 cursor-not-allowed">
                            Next
                        </span>
                        @endif
                    </div>

                    <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Showing
                                <span class="font-medium">{{ $exports->firstItem() ?? 0 }}</span>
                                to
                                <span class="font-medium">{{ $exports->lastItem() ?? 0 }}</span>
                                of
                                <span class="font-medium">{{ $exports->total() }}</span>
                                results
                            </p>
                        </div>
                        <div>
                            <div class="inline-flex rounded-md shadow-sm space-x-2">
                                @if($exports->onFirstPage())
                                <span class="relative inline-flex items-center rounded-l-md border border-gray-300 bg-white px-2 py-2 text-sm font-medium text-gray-500 cursor-not-allowed">
                                    <span class="sr-only">Previous</span>
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </span>
                                @else
                                <a href="{{ $exports->previousPageUrl() }}" class="relative inline-flex items-center rounded-l-md border border-gray-300 bg-white px-2 py-2 text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <span class="sr-only">Previous</span>
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </a>
                                @endif

                                @foreach($exports->getUrlRange(1, $exports->lastPage()) as $page => $url)
                                @if($page == $exports->currentPage())
                                <span class="relative z-10 inline-flex items-center border border-blue-500 bg-blue-50 px-4 py-2 text-sm font-medium text-blue-600 cursor-default">
                                    {{ $page }}
                                </span>
                                @else
                                <a href="{{ $url }}" class="relative inline-flex items-center border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    {{ $page }}
                                </a>
                                @endif
                                @endforeach

                                @if($exports->hasMorePages())
                                <a href="{{ $exports->nextPageUrl() }}" class="relative inline-flex items-center rounded-r-md border border-gray-300 bg-white px-2 py-2 text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <span class="sr-only">Next</span>
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </a>
                                @else
                                <span class="relative inline-flex items-center rounded-r-md border border-gray-300 bg-white px-2 py-2 text-sm font-medium text-gray-500 cursor-not-allowed">
                                    <span class="sr-only">Next</span>
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </nav>
            </div>
            @endif
            @else
            <!-- Empty State -->
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.163 7.5C9.163 5.145 10.932 3.276 13.288 3.276c2.356 0 4.125 1.869 4.125 4.224M18.414 12c0 1.657-.673 3.247-1.761 4.336a6.175 6.175 0 01-4.353 1.841H9a6 6 0 110-12h3.3a6.175 6.175 0 014.353 1.841A6.176 6.176 0 0118.414 12z" />
                </svg>
                <h3 class="mt-2 text-lg font-medium text-gray-900">No videos yet</h3>
                <p class="mt-1 text-gray-500">Start creating exports to see them here.</p>
            </div>
            @endif
        </div>
    </div>

    <!-- Add javascript from laravel -->
    @vite('resources/js/app.js')
</body>

</html>