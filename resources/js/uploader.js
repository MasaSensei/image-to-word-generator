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
                this.errorMessage = `Maximum allowed is ${this.maxImages} images.`;
                return;
            }

            filesArray.forEach((file) => {
                if (!file.type.match("image.*")) return;

                if (file.size > this.maxSize) {
                    this.errorMessage = `"${file.name}" exceeds the maximum file size.`;
                    return;
                }

                this.files.push({
                    id: Math.random().toString(36).substr(2, 9),
                    raw: file,
                    preview: URL.createObjectURL(file),
                    description: "",
                });
            });
        },

        removeFile(index) {
            URL.revokeObjectURL(this.files[index].preview);
            this.files.splice(index, 1);
        },

        async generateWord() {
            this.isGenerating = true;
            this.errorMessage = "";

            try {
                const formData = new FormData();
                this.files.forEach((f) => formData.append("images[]", f.raw));
                this.files.forEach((f) =>
                    formData.append("descriptions[]", f.description || ""),
                );

                const res = await fetch('{{ route("word.generate") }}', {
                    method: "POST",
                    body: formData,
                    headers: {
                        "X-CSRF-TOKEN": document.querySelector(
                            'meta[name="csrf-token"]',
                        ).content,
                    },
                });

                if (!res.ok) {
                    const err = await res.json();
                    this.errorMessage =
                        err.message || "Failed to start the process.";
                    this.isGenerating = false;
                    return;
                }

                const { job_id } = await res.json();
                this.pollStatus(job_id);
            } catch (e) {
                this.errorMessage = "A network error occurred.";
                this.isGenerating = false;
            }
        },

        pollStatus(jobId) {
            const interval = setInterval(async () => {
                try {
                    const res = await fetch(`/generate/status/${jobId}`);
                    const s = await res.json();

                    if (s.status === "completed") {
                        clearInterval(interval);
                        this.isGenerating = false;
                        window.location.href = s.download_url;
                    } else if (s.status === "failed") {
                        clearInterval(interval);
                        this.isGenerating = false;
                        this.errorMessage =
                            s.error_message ||
                            "Failed to generate the document.";
                    }
                } catch (e) {
                    clearInterval(interval);
                    this.isGenerating = false;
                    this.errorMessage =
                        "Lost connection while checking status.";
                }
            }, 2000);
        },
    }));
});
