document.addEventListener("alpine:init", () => {
    Alpine.data("imageUploader", (config) => ({
        files: [],
        errorMessage: "",
        isGenerating: false,
        showGuide: false,
        maxImages: config.maxImages,
        maxSize: config.maxSize,
        generateUrl: config.generateUrl,
        statusUrlBase: config.statusUrlBase,

        openHistory: true,
        historyUrl: config.historyUrl,
        historyPage: config.historyPage || 1,

        activePollJobId: null,
        pollTimeoutId: null,

        downloadingHistoryId: null,

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

        resetWorkspace() {
            this.files.forEach((f) => URL.revokeObjectURL(f.preview));
            this.files = [];
        },

        async generateWord() {
            if (this.isGenerating) {
                console.debug(
                    "[Generate] Ignored: a generation is already in progress.",
                );
                return;
            }

            this.isGenerating = true;
            this.errorMessage = "";

            try {
                const formData = new FormData();
                this.files.forEach((f) => formData.append("images[]", f.raw));
                this.files.forEach((f) =>
                    formData.append("descriptions[]", f.description || ""),
                );

                const res = await fetch(this.generateUrl, {
                    method: "POST",
                    body: formData,
                    headers: {
                        "X-CSRF-TOKEN": document.querySelector(
                            'meta[name="csrf-token"]',
                        ).content,
                        Accept: "application/json",
                    },
                });

                if (!res.ok) {
                    let message = `Request failed (HTTP ${res.status}).`;

                    const contentType = res.headers.get("content-type") || "";
                    if (contentType.includes("application/json")) {
                        const err = await res.json();
                        message = err.message || message;
                    } else if (res.status === 404) {
                        message =
                            "The generate endpoint could not be found (404). Check your route configuration.";
                    } else if (res.status === 419) {
                        message =
                            "Your session has expired. Please refresh the page and try again.";
                    } else if (res.status >= 500) {
                        message =
                            "Server error while starting the process. Please try again.";
                    }

                    this.$store.toasts.push(message, "error");
                    this.isGenerating = false;
                    return;
                }

                const { job_id } = await res.json();
                console.debug(`[Generate] Job started: ${job_id}`);
                this.startPolling(job_id);
            } catch (e) {
                this.$store.toasts.push(
                    "A network error occurred. Please check your connection.",
                    "error",
                );
                this.isGenerating = false;
            }
        },

        startPolling(jobId) {
            if (this.activePollJobId === jobId) {
                console.debug(
                    `[Poll] ${jobId}: polling already active, ignoring duplicate start.`,
                );
                return;
            }

            this.stopPolling();

            this.activePollJobId = jobId;
            this.scheduleNextPoll(jobId, 0);
        },

        stopPolling() {
            if (this.pollTimeoutId !== null) {
                clearTimeout(this.pollTimeoutId);
                this.pollTimeoutId = null;
            }
            this.activePollJobId = null;
        },

        scheduleNextPoll(jobId, delay) {
            this.pollTimeoutId = setTimeout(
                () => this.pollStatus(jobId),
                delay,
            );
        },

        async pollStatus(jobId) {
            if (this.activePollJobId !== jobId) {
                return;
            }

            try {
                const res = await fetch(`${this.statusUrlBase}/${jobId}`, {
                    headers: { Accept: "application/json" },
                });
                const s = await res.json();

                if (this.activePollJobId !== jobId) {
                    return;
                }

                console.debug(`[Poll] ${jobId}: ${s.status}`);

                if (s.status === "completed") {
                    this.stopPolling();
                    this.isGenerating = false;
                    console.debug(`[Poll] Completed: ${jobId}`);
                    this.$store.toasts.push(
                        "Document generated successfully.",
                        "success",
                    );

                    this.refreshHistory();
                    this.resetWorkspace();

                    setTimeout(() => {
                        console.debug(`[Poll] Downloading: ${jobId}`);
                        window.location.href = s.download_url;
                    }, 1000);
                    return;
                }

                if (s.status === "failed") {
                    this.stopPolling();
                    this.isGenerating = false;
                    console.debug(`[Poll] Failed: ${jobId}`);
                    this.$store.toasts.push(
                        s.error_message || "Failed to generate the document.",
                        "error",
                    );
                    return;
                }

                this.scheduleNextPoll(jobId, 2000);
            } catch (e) {
                if (this.activePollJobId !== jobId) {
                    return;
                }
                this.stopPolling();
                this.isGenerating = false;
                this.$store.toasts.push(
                    "Lost connection while checking status.",
                    "error",
                );
            }
        },

        async downloadHistory(url, id, fileName) {
            if (this.downloadingHistoryId !== null) {
                console.debug(
                    `[History] Ignored: another download is already in progress.`,
                );
                return;
            }

            this.downloadingHistoryId = id;
            console.debug(`[History] Download started: ${id}`);

            try {
                const res = await fetch(url, {
                    headers: { Accept: "application/json" },
                });

                if (!res.ok) {
                    let message =
                        "This document has expired and is no longer available.";

                    const contentType = res.headers.get("content-type") || "";
                    if (contentType.includes("application/json")) {
                        const err = await res.json();
                        message = err.message || message;
                    }

                    console.debug(`[History] Failed: ${id}`);
                    this.$store.toasts.push(message, "error");
                    return;
                }

                const blob = await res.blob();
                const blobUrl = URL.createObjectURL(blob);

                const disposition =
                    res.headers.get("content-disposition") || "";
                const match = disposition.match(/filename="?([^"]+)"?/i);
                const downloadName = match
                    ? match[1]
                    : fileName || "document.docx";

                const link = document.createElement("a");
                link.href = blobUrl;
                link.download = downloadName;
                document.body.appendChild(link);
                link.click();
                link.remove();
                URL.revokeObjectURL(blobUrl);

                console.debug(`[History] Downloaded: ${id}`);
                this.$store.toasts.push("Download started.", "success");
            } catch (e) {
                console.debug(`[History] Network error: ${id}`);
                this.$store.toasts.push(
                    "A network error occurred while downloading.",
                    "error",
                );
            } finally {
                this.downloadingHistoryId = null;
            }
        },

        async refreshHistory() {
            if (!this.historyUrl || !this.$refs.historyContainer) {
                return;
            }

            try {
                const res = await fetch(
                    `${this.historyUrl}?page=${this.historyPage}`,
                    { headers: { Accept: "application/json" } },
                );

                if (!res.ok) {
                    console.debug("[History] Refresh failed (non-blocking).");
                    return;
                }

                const data = await res.json();
                const container = this.$refs.historyContainer;

                Alpine.destroyTree(container);
                container.innerHTML = data.html;
                Alpine.initTree(container);

                if (data.current_page) {
                    this.historyPage = data.current_page;
                }

                console.debug("[History] Refreshed.");
            } catch (e) {
                console.debug("[History] Refresh failed (non-blocking).");
            }
        },
    }));
});
