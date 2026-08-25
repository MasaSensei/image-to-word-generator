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
                if (!file.type.match("image.*")) return;

                this.files.push({
                    id: Math.random().toString(36).substr(2, 9),
                    raw: file,
                    preview: URL.createObjectURL(file),
                    description: "", // Tambahkan variabel default untuk input teks
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
                // Batasi keamanan maksimal deskripsi 150 char di sisi JS sebelum dikirim
                const safeDesc = file.description
                    ? file.description.substring(0, 150)
                    : "";
                formData.append(`descriptions[${index}]`, safeDesc);
            });

            try {
                const response = await fetch("/generate", {
                    method: "POST",
                    body: formData,
                    headers: {
                        "X-CSRF-TOKEN":
                            document
                                .querySelector('meta[name="csrf-token"]')
                                ?.getAttribute("content") || "",
                    },
                });

                if (!response.ok) {
                    const errorData = await response.json().catch(() => null);
                    throw new Error(
                        errorData?.message || "Gagal memproses dokumen.",
                    );
                }

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
