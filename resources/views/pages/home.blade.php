@extends('layouts.app')

@section('content')
    <div x-data="imageUploader({
        maxImages: {{ config('image_to_word.max_images') }},
        maxSize: {{ config('image_to_word.max_file_size') * 1024 }},
        generateUrl: '{{ route('word.generate') }}',
        statusUrlBase: '{{ url('/generate/status') }}',
        historyUrl: '{{ route('word.history') }}',
        historyPage: {{ isset($histories) && is_object($histories) ? $histories->currentPage() : 1 }}
    })" class="bg-paper-card rounded-sm shadow-paper border border-paper-line">

        <div class="px-8 pt-8 pb-6 border-b border-paper-line">
            <h1 class="font-serif text-2xl font-semibold text-ink">Convert Document</h1>
            <p class="text-sm text-ink-muted mt-1">Upload images, arrange the order, then merge them into a
                print-ready Word file.</p>
        </div>

        <!-- Stepper alur: not a separate guide panel, but a map of the current step -->
        <div class="px-8 py-5 border-b border-paper-line bg-paper">
            <ol class="flex items-center text-xs font-medium text-ink-muted uppercase tracking-wider">
                <li class="flex items-center" :class="files.length === 0 ? 'text-brand-600' : 'text-ink'">
                    <span class="w-5 h-5 flex items-center justify-center rounded-full border text-[10px] mr-2"
                        :class="files.length === 0 ? 'border-brand-600 text-brand-600' : 'border-ink-muted/40'">1</span>
                    Upload Images
                </li>
                <li class="w-8 h-px bg-paper-line mx-3"></li>
                <li class="flex items-center" :class="files.length > 0 ? 'text-brand-600' : 'text-ink-muted'">
                    <span class="w-5 h-5 flex items-center justify-center rounded-full border text-[10px] mr-2"
                        :class="files.length > 0 ? 'border-brand-600 text-brand-600' : 'border-ink-muted/40'">2</span>
                    Arrange & Caption
                </li>
                <li class="w-8 h-px bg-paper-line mx-3"></li>
                <li class="flex items-center text-ink-muted">
                    <span
                        class="w-5 h-5 flex items-center justify-center rounded-full border border-ink-muted/40 text-[10px] mr-2">3</span>
                    Generate Document
                </li>
            </ol>
        </div>

        <div class="p-8">
            <!-- Dropzone Area -->
            <div class="border-2 border-dashed border-brand-600/30 rounded-sm p-12 text-center hover:bg-brand-50/40 hover:border-brand-600 transition cursor-pointer group"
                @click="$refs.fileInput.click()" @dragover.prevent="$el.classList.add('border-brand-600', 'bg-brand-50/40')"
                @dragleave.prevent="$el.classList.remove('border-brand-600', 'bg-brand-50/40')"
                @drop.prevent="handleDrop($event); $el.classList.remove('border-brand-600', 'bg-brand-50/40')">

                <input type="file" x-ref="fileInput" multiple accept=".jpg,.jpeg,.png" class="hidden"
                    @change="handleFiles($event)">

                <div class="text-ink-muted group-hover:text-brand-700 transition">
                    <svg class="mx-auto h-9 w-9 mb-4 text-ink-muted/60 group-hover:text-brand-600" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12">
                        </path>
                    </svg>
                    <p class="font-medium text-ink">Drag & drop your files here, or click to browse</p>
                    <p class="text-xs mt-2 text-ink-muted">Supported: JPG, PNG &middot; Max
                        {{ config('image_to_word.max_images') }} files,
                        {{ config('image_to_word.max_file_size') / 1024 }}MB per file.</p>
                </div>
            </div>

            <!-- Error Alert -->
            <template x-if="errorMessage">
                <div
                    class="mt-6 p-4 bg-brand-50 border-l-2 border-brand-600 text-brand-900 text-sm font-medium rounded-r-sm">
                    <span x-text="errorMessage"></span>
                </div>
            </template>

            <!-- Workspace & Preview -->
            <div class="mt-10" x-show="files.length > 0" style="display: none;">
                <div class="flex justify-between items-center mb-5 pb-2 border-b border-paper-line">
                    <h2 class="font-serif text-base font-semibold text-ink">Workspace</h2>
                    <span
                        class="text-xs font-medium px-2.5 py-1 bg-paper text-ink-muted rounded-sm uppercase tracking-wider border border-paper-line"
                        x-text="files.length + ' Documents'"></span>
                </div>

                <!-- Sortable grid with caption form -->
                <div x-ref="gallery" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <template x-for="(file, index) in files" :key="file.id">
                        <div
                            class="group relative cursor-move bg-paper-card border border-paper-line hover:shadow-paper transition rounded-sm flex flex-col overflow-hidden">

                            <!-- Archive tab: document-folder-style page number, not a gradient overlay -->
                            <div
                                class="absolute -top-px left-4 z-10 bg-brand-600 text-paper text-[10px] font-semibold tracking-wider uppercase px-2.5 py-1 rounded-b-sm shadow-sm">
                                Page <span x-text="Math.floor(index / 2) + 1"></span>
                            </div>

                            <button @click.stop="removeFile(index)"
                                class="absolute top-2 right-2 z-10 bg-paper-card text-brand-600 p-1.5 rounded-sm border border-paper-line opacity-0 group-hover:opacity-100 hover:bg-brand-50 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                    </path>
                                </svg>
                            </button>

                            <!-- Image wrapper (4:3 ratio) -->
                            <div class="w-full aspect-[4/3] bg-paper-line/40">
                                <img :src="file.preview" class="w-full h-full object-cover">
                            </div>

                            <!-- Image Caption Form -->
                            <div class="p-3 border-t border-paper-line flex-1 flex flex-col justify-center">
                                <div class="flex items-center gap-1.5 mb-1.5">
                                    <span
                                        class="inline-flex items-center justify-center w-5 h-5 text-[10px] font-semibold text-brand-600 bg-brand-50 border border-brand-600/30 rounded-full"
                                        x-text="index + 1"></span>
                                    <span class="text-[10px] text-ink-muted uppercase tracking-wider">Caption</span>
                                </div>
                                <textarea x-model="file.description" maxlength="150" rows="2" placeholder="Image caption (optional)..."
                                    class="w-full text-xs p-2 border border-paper-line rounded-sm focus:outline-none focus:border-brand-600 focus:ring-1 focus:ring-brand-600 transition bg-paper resize-none"></textarea>
                                <div class="text-right mt-1 text-[10px] text-ink-muted">
                                    <span x-text="file.description ? file.description.length : 0"></span>/150 Characters
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Submit Button -->
                <div class="mt-10 flex justify-end">
                    <button @click="generateWord" :disabled="isGenerating"
                        class="bg-brand-600 hover:bg-brand-700 text-paper font-medium text-sm py-2.5 px-6 rounded-sm shadow-paper disabled:opacity-50 transition flex items-center">
                        <span x-show="!isGenerating">Generate Word Document</span>
                        <span x-show="isGenerating">Processing...</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Document History Section -->
        <div class="border-t border-paper-line">
            <div class="flex justify-between items-center px-8 py-5">
                <h2 class="font-serif text-lg font-semibold text-ink">Document History</h2>
                <button @click="openHistory = !openHistory"
                    class="text-xs text-brand-600 hover:text-brand-700 font-medium uppercase tracking-wider">
                    <span x-text="openHistory ? 'Hide' : 'Show'"></span>
                </button>
            </div>

            <div x-show="openHistory" class="px-8 pb-8">
                <div x-ref="historyContainer">
                    @include('pages.partials.history')
                </div>
            </div>
        </div>

        <!-- Processing Overlay -->
        <div x-show="isGenerating" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-40 bg-ink/60 backdrop-blur-sm flex items-center justify-center" style="display: none;">

            <div
                class="bg-paper-card rounded-sm shadow-paper border border-paper-line p-8 max-w-sm w-full mx-4 text-center">
                <!-- Spinner -->
                <svg class="animate-spin h-8 w-8 text-brand-600 mx-auto mb-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                        stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>

                <h3 class="font-serif text-base font-semibold text-ink mb-1">Generating Document</h3>
                <p class="text-xs text-ink-muted">
                    Processing <span x-text="files.length"></span> images. This may take a moment for large batches — feel
                    free to keep this tab open.
                </p>
            </div>
        </div>
    </div>
@endsection
