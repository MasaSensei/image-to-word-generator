document.addEventListener("alpine:init", () => {
    Alpine.data("imageUploader", (config) => ({
        files: [],
        errorMessage: "",
        isGenerating: false,
        showGuide: false,
        maxImages: config.maxImages,
        maxSize: config.maxSize,

        init() {
            setTimeout(() => {
                if (typeof Sortable !== "undefined" && this.$refs.gallery) {
                    Sortable.create(this.$refs.gallery, {
                        animation: 200,
                        ghostClass: "opacity-40",
                        dragClass: "ring-2",
                        onEnd: (e) => {
                            const item = this.files.splice(e.oldIndex, 1)[0];
                            this.files.splice(e.newIndex, 0, item);
                        },
                    });
                }
            }, 100);
        },

        handleDrop(e) {
            this.addFiles(e.dataTransfer.files);
        },

        handleFiles(e) {
            this.addFiles(e.target.files);
            e.target.value = "";
        },

        addFiles(newFiles) {
            this.errorMessage = "";
            let filesArray = Array.from(newFiles);

            if (this.files.length + filesArray.length > this.maxImages) {
                this.errorMessage = `Batas maksimal adalah ${this.maxImages} dokumen gambar.`;
                return;
            }

            filesArray.forEach((file) => {
                if (!file.type.match("image.*")) {
                    this.errorMessage =
                        "Format tidak valid. Gunakan ekstensi korporat standar (JPG, PNG).";
                    return;
                }
                if (file.size > this.maxSize) {
                    this.errorMessage = `Ukuran dokumen ${file.name} melebihi batas yang diizinkan.`;
                    return;
                }

                this.files.push({
                    id: Math.random().toString(36).substr(2, 9),
                    raw: file,
                    preview: URL.createObjectURL(file),
                });
            });
        },

        removeFile(index) {
            URL.revokeObjectURL(this.files[index].preview);
            this.files.splice(index, 1);
        },

        async generateWord() {
            if (this.files.length === 0) return;

            this.isGenerating = true;
            this.errorMessage = "";

            let formData = new FormData();
            this.files.forEach((file, index) => {
                formData.append(`images[${index}]`, file.raw);
            });

            try {
                const response = await fetch("/generate", {
                    method: "POST",
                    body: formData,
                    headers: {
                        "X-CSRF-TOKEN":
                            document
                                .querySelector('meta[name="csrf-token"]')
                                ?.getAttribute("content") ||
                            "{{ csrf_token() }}",
                    },
                });

                // Cek apakah response berupa error JSON
                const contentType = response.headers.get("content-type");
                if (!response.ok) {
                    if (
                        contentType &&
                        contentType.includes("application/json")
                    ) {
                        const errorData = await response.json();
                        throw new Error(
                            errorData.message || "Gagal memproses dokumen.",
                        );
                    } else {
                        throw new Error(
                            "Terjadi kesalahan server saat men-generate dokumen.",
                        );
                    }
                }

                // Jika sukses, download file blob
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement("a");
                a.href = url;
                a.download = `Corporate_Report_${Date.now()}.docx`;
                document.body.appendChild(a);
                a.click();
                a.remove();
            } catch (error) {
                this.errorMessage = error.message;
            } finally {
                this.isGenerating = false;
            }
        },
    }));
});
