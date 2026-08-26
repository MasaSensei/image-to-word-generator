document.addEventListener("alpine:init", () => {
    Alpine.store("toasts", {
        list: [],
        push(message, type = "error") {
            const id = Date.now() + Math.random();
            this.list.push({ id, message, type });
            setTimeout(() => {
                this.list = this.list.filter((t) => t.id !== id);
            }, 4000);
        },
    });
});
