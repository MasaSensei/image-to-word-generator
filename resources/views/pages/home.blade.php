@extends('layouts.app')

@section('content')
    <!-- Inject config dari PHP ke JavaScript -->
    <div x-data="imageUploader({
        maxImages: {{ config('image_to_word.max_images') }},
        maxSize: {{ config('image_to_word.max_file_size') * 1024 }}
    })" class="bg-white p-8 rounded-sm shadow-sm border border-slate-200">

        <div class="mb-8 flex justify-between items-center border-b border-slate-100 pb-4">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">Konversi Dokumen (Image to Word)</h1>
                <p class="text-sm text-slate-500 mt-1">Unggah dokumen gambar Anda untuk digabungkan menjadi format Microsoft
                    Word profesional.</p>
            </div>
            <!-- Tombol Toggle Panduan -->
            <button @click="showGuide = !showGuide"
                class="text-xs font-medium px-3 py-1.5 bg-slate-100 text-slate-700 hover:bg-slate-200 rounded-sm transition flex items-center space-x-1">
                <svg class="w-4 h-4 mr-1 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span x-text="showGuide ? 'Sembunyikan Panduan' : 'Panduan Sistem'"></span>
            </button>
        </div>

        <!-- Panel Panduan Interaktif (Collapsible) -->
        <div x-show="showGuide" x-transition class="mb-8 p-6 bg-brand-50 border border-brand-600/20 rounded-sm">
            <h2 class="text-sm font-bold text-brand-900 uppercase tracking-wider mb-3">Panduan Penggunaan Singkat</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs text-slate-700">
                <div class="bg-white p-4 border border-brand-600/10 shadow-xs">
                    <span class="font-bold text-brand-600 block mb-1">01. Unggah Gambar</span>
                    Seret dan lepas (drag & drop) berkas gambar JPG/PNG ke area kotak di bawah, atau klik untuk memilih dari
                    komputer Anda.
                </div>
                <div class="bg-white p-4 border border-brand-600/10 shadow-xs">
                    <span class="font-bold text-brand-600 block mb-1">02. Atur Urutan Halaman</span>
                    Geser posisi kartu gambar sesuai keinginan. Sistem otomatis menyusun 2 gambar per halaman laporan Word.
                </div>
                <div class="bg-white p-4 border border-brand-600/10 shadow-xs">
                    <span class="font-bold text-brand-600 block mb-1">03. Generate & Riwayat</span>
                    Klik proses untuk mengunduh file .docx. Riwayat unduhan akan otomatis tersimpan privat di perangkat
                    Anda.
                </div>
            </div>
        </div>

        <!-- Dropzone Area -->
        <div class="border-2 border-dashed border-slate-300 rounded-sm p-12 text-center hover:bg-slate-50 hover:border-brand-600 transition cursor-pointer group"
            @click="$refs.fileInput.click()" @dragover.prevent="$el.classList.add('border-brand-600', 'bg-brand-50')"
            @dragleave.prevent="$el.classList.remove('border-brand-600', 'bg-brand-50')"
            @drop.prevent="handleDrop($event); $el.classList.remove('border-brand-600', 'bg-brand-50')">

            <input type="file" x-ref="fileInput" multiple accept=".jpg,.jpeg,.png" class="hidden"
                @change="handleFiles($event)">

            <div class="text-slate-500 group-hover:text-brand-900 transition">
                <svg class="mx-auto h-10 w-10 mb-4 text-slate-400 group-hover:text-brand-600" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                </svg>
                <p class="font-medium">Tarik & lepas dokumen di sini, atau klik untuk memilih file</p>
                <p class="text-xs mt-2 text-slate-400">Dukungan: JPG, PNG | Maks: {{ config('image_to_word.max_images') }}
                    berkas, {{ config('image_to_word.max_file_size') / 1024 }}MB/berkas.</p>
            </div>
        </div>

        <!-- Error Alert -->
        <template x-if="errorMessage">
            <div class="mt-6 p-4 bg-red-50 border-l-4 border-red-600 text-red-800 text-sm font-medium rounded-r-sm">
                <span x-text="errorMessage"></span>
            </div>
        </template>

        <!-- Workspace & Preview -->
        <div class="mt-10" x-show="files.length > 0" style="display: none;">
            <div class="flex justify-between items-center mb-4 pb-2 border-b border-slate-100">
                <h2 class="text-base font-semibold text-slate-800">Ruang Kerja (Pengurutan Halaman)</h2>
                <span
                    class="text-xs font-semibold px-2.5 py-1 bg-slate-100 text-slate-600 rounded-sm uppercase tracking-wider"
                    x-text="files.length + ' Dokumen'"></span>
            </div>

            <!-- Sortable Grid -->
            <div x-ref="gallery" class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <template x-for="(file, index) in files" :key="file.id">
                    <div
                        class="relative group cursor-move bg-slate-100 border border-slate-200 aspect-[4/3] shadow-sm hover:shadow-md transition">
                        <img :src="file.preview" class="w-full h-full object-cover">

                        <div class="absolute top-0 left-0 w-full p-2 bg-gradient-to-b from-slate-900/60 to-transparent">
                            <span class="text-white text-xs font-medium tracking-wide">
                                Halaman <span x-text="Math.floor(index / 2) + 1"></span>
                            </span>
                        </div>

                        <button @click.stop="removeFile(index)"
                            class="absolute top-2 right-2 bg-white text-red-600 p-1.5 rounded-sm shadow-sm opacity-0 group-hover:opacity-100 hover:bg-red-50 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                </path>
                            </svg>
                        </button>
                    </div>
                </template>
            </div>

            <!-- Submit Button -->
            <div class="mt-10 flex justify-end">
                <button @click="generateWord" :disabled="isGenerating"
                    class="bg-brand-600 hover:bg-brand-900 text-white font-medium text-sm py-2.5 px-6 rounded-sm shadow-sm disabled:opacity-50 transition flex items-center">
                    <span x-show="!isGenerating">Proses menjadi Dokumen Word</span>
                    <span x-show="isGenerating">Memproses Data...</span>
                </button>
            </div>
        </div>

        <!-- Bagian Riwayat Dokumen (History) -->
        <div class="mt-16 pt-8 border-t border-slate-200" x-data="{ openHistory: true }">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-lg font-semibold text-slate-900">Riwayat Dokumen Anda</h2>
                <button @click="openHistory = !openHistory" class="text-xs text-brand-600 hover:underline font-medium">
                    <span x-text="openHistory ? 'Sembunyikan' : 'Tampilkan'"></span>
                </button>
            </div>

            <div x-show="openHistory" class="bg-slate-50 border border-slate-200 rounded-sm overflow-hidden">
                @if (isset($histories) && count($histories) > 0)
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="bg-slate-100 border-b border-slate-200 text-slate-600">
                                <th class="p-3 font-medium">Nama Berkas</th>
                                <th class="p-3 font-medium">Jumlah Gambar</th>
                                <th class="p-3 font-medium">Waktu Dibuat</th>
                                <th class="p-3 font-medium text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 text-slate-700">
                            @foreach ($histories as $history)
                                <tr class="hover:bg-white transition">
                                    <td class="p-3 font-medium text-slate-900">{{ $history->file_name }}</td>
                                    <td class="p-3">{{ $history->image_count }} Gambar</td>
                                    <td class="p-3 text-slate-500">{{ $history->created_at->format('d M Y, H:i') }}</td>
                                    <td class="p-3 text-right">
                                        <a href="{{ route('history.download', $history->id) }}"
                                            class="inline-flex items-center text-xs font-medium text-brand-600 hover:text-brand-900 bg-white border border-slate-300 px-3 py-1.5 rounded-sm shadow-sm transition">
                                            Unduh Ulang
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="p-4 border-t border-slate-200">
                        {{ $histories->links() }}
                    </div>
                @else
                    <div class="p-8 text-center text-slate-400 text-sm">
                        Belum ada riwayat dokumen yang dibuat dari perangkat ini.
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
