import "./bootstrap";

import Alpine from "alpinejs";

window.Alpine = Alpine;

Alpine.start();

function createRepliesGroup() {
    const group = document.createElement("div");

    group.className = "mt-2";
    group.dataset.repliesGroup = "";
    group.setAttribute("x-data", "{ open: true, count: 1 }");

    const button = document.createElement("button");

    button.type = "button";
    button.className =
        "text-[11px] text-slate-500 font-semibold cursor-pointer flex items-center gap-1 hover:text-slate-700 transition-colors";
    button.setAttribute("@click", "open = !open");
    button.setAttribute(":aria-expanded", "open");

    const arrow = document.createElement("span");
    arrow.setAttribute("x-text", "open ? '▼' : '▶'");

    const count = document.createElement("span");
    count.dataset.repliesCount = "";
    count.setAttribute("x-text", "open ? 'Sembunyikan balasan' : 'Lihat ' + count + ' balasan'");
    count.textContent = "Sembunyikan balasan";

    button.append(arrow, count);

    const list = document.createElement("div");

    list.className = "mt-2";
    list.dataset.repliesList = "";
    list.setAttribute("x-show", "open");

    group.append(button, list);

    return group;
}

document.addEventListener("submit", async function (event) {
    const form = event.target.closest("form[data-comment-ajax]");

    if (!form) {
        return;
    }

    event.preventDefault();

    const button = form.querySelector('button[type="submit"]');
    let originalBtnHtml = "";

    if (button) {
        button.disabled = true;
        originalBtnHtml = button.innerHTML;
        button.innerHTML = `<svg class="animate-spin -ml-1 mr-1.5 h-3.5 w-3.5 inline text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Memproses...`;
    }

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]');

        const response = await fetch(form.action, {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": csrfToken ? csrfToken.content : "",
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
            },
            body: new FormData(form),
            credentials: "same-origin",
        });

        const data = await response.json();

        if (!response.ok) {
            const errors = data.errors
                ? Object.values(data.errors).flat().join("\n")
                : data.message || "Terjadi kesalahan.";

            alert(errors);
            return;
        }

        const post = form.closest("[data-post-id]");

        if (form.dataset.commentAjax === "store") {
            const commentsList = post ? post.querySelector("[data-comments-list]") : null;

            const template = document.createElement("template");
            template.innerHTML = data.html.trim();

            if (data.parent_id) {
                const parent = post.querySelector(
                    `[data-comment-id="${data.parent_id}"]`,
                );

                const repliesContainer = parent.querySelector(
                    "[data-replies-container]",
                );

                let group = repliesContainer.querySelector(
                    ":scope > [data-replies-group]",
                );

                let isNewGroup = false;

                if (!group) {
                    group = createRepliesGroup();
                    repliesContainer.appendChild(group);
                    isNewGroup = true;
                }

                const repliesList = group.querySelector("[data-replies-list]");

                repliesList.appendChild(template.content);

                if (isNewGroup) {
                    window.Alpine.initTree(group);
                } else {
                    const alpineData = window.Alpine?.$data(group);
                    if (alpineData) {
                        alpineData.count = repliesList.children.length;
                        alpineData.open = true;
                    }
                }
            } else {
                if (commentsList) {
                    const emptyMessage = commentsList.querySelector(
                        "[data-empty-comments]",
                    );

                    if (emptyMessage) {
                        emptyMessage.remove();
                    }

                    const newElements = Array.from(template.content.children);
                    newElements.forEach((el) => {
                        commentsList.appendChild(el);
                        if (window.Alpine) {
                            window.Alpine.initTree(el);
                        }
                    });
                }
            }

            if (post) {
                const countElement = post.querySelector("[data-comment-count]");

                if (countElement) {
                    const currentCount = parseInt(
                        countElement.dataset.count || "0",
                        10,
                    );

                    const newCount = currentCount + 1;

                    countElement.dataset.count = newCount;
                    countElement.textContent = `💬 ${newCount} Komentar`;
                }
            }

            form.reset();

            const details = form.closest("details");

            if (details) {
                details.open = false;
            }
        }

        if (
            form.dataset.commentAjax === "update" ||
            form.dataset.commentAjax === "delete"
        ) {
            const comment = form.closest("[data-comment-id]");

            const template = document.createElement("template");
            template.innerHTML = data.html.trim();

            const newBody = template.content.querySelector(
                "[data-comment-body]",
            );

            const oldBody = comment.querySelector("[data-comment-body]");

            if (newBody && oldBody) {
                oldBody.replaceWith(newBody);
            }
        }
    } catch (error) {
        alert("Gagal menghubungi server. Silakan coba lagi.");
        console.error(error);
    } finally {
        if (button) {
            if (originalBtnHtml) {
                button.innerHTML = originalBtnHtml;
            }
            button.disabled = false;
        }
    }
});

// Tombol Batal pada Form Komentar, Balasan, dan Edit
document.addEventListener("click", function (event) {
    const cancelBtn = event.target.closest("[data-comment-cancel]");
    if (!cancelBtn) return;

    const form = cancelBtn.closest("form");
    if (!form) return;

    const textarea = form.querySelector("textarea[name='content']");
    const cancelType = cancelBtn.dataset.commentCancel;

    if (cancelType === "store" || cancelType === "reply") {
        if (textarea) {
            textarea.value = "";
        }
    } else if (cancelType === "edit") {
        if (textarea) {
            textarea.value = textarea.dataset.originalContent || textarea.defaultValue || "";
        }
    }

    const details = form.closest("details");
    if (details) {
        details.open = false;
    }
});

// AJAX Pagination "See more" Komentar
document.addEventListener("click", async function (event) {
    const loadMoreBtn = event.target.closest("[data-load-more-comments]");
    if (!loadMoreBtn) return;

    const url = loadMoreBtn.dataset.loadMoreComments;
    const nextPage = parseInt(loadMoreBtn.dataset.nextPage || "2", 10);
    const container = loadMoreBtn.closest("[data-load-more-container]");
    const post = loadMoreBtn.closest("[data-post-id]");
    const commentsList = post ? post.querySelector("[data-comments-list]") : null;

    if (!url || !commentsList) return;

    loadMoreBtn.disabled = true;
    const originalText = loadMoreBtn.innerHTML;
    loadMoreBtn.innerHTML = `<svg class="animate-spin -ml-1 mr-1.5 h-3.5 w-3.5 inline text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Memuat...`;

    try {
        const response = await fetch(`${url}?page=${nextPage}`, {
            headers: {
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
            },
        });

        if (!response.ok) {
            throw new Error("Gagal memuat komentar.");
        }

        const data = await response.json();
        const template = document.createElement("template");
        template.innerHTML = data.html.trim();

        // Mencegah komentar duplikat
        const existingIds = new Set(
            Array.from(commentsList.querySelectorAll("[data-comment-id]")).map(
                (el) => el.dataset.commentId,
            ),
        );

        const newNodes = Array.from(template.content.children);
        newNodes.forEach((node) => {
            const commentId = node.dataset?.commentId;
            if (!commentId || !existingIds.has(commentId)) {
                commentsList.appendChild(node);
                if (window.Alpine) {
                    window.Alpine.initTree(node);
                }
            }
        });

        if (data.has_more) {
            loadMoreBtn.dataset.nextPage = (nextPage + 1).toString();
            loadMoreBtn.innerHTML = originalText;
            loadMoreBtn.disabled = false;
        } else {
            if (container) {
                container.remove();
            } else {
                loadMoreBtn.remove();
            }
        }
    } catch (error) {
        alert("Gagal memuat komentar berikutnya. Silakan coba lagi.");
        console.error(error);
        loadMoreBtn.innerHTML = originalText;
        loadMoreBtn.disabled = false;
    }
});

window.axios.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response && [401, 419].includes(error.response.status)) {
            if (window.dispatchEvent) {
                window.dispatchEvent(
                    new CustomEvent("open-login-modal", {
                        detail: {
                            message:
                                "Sesi Anda telah berakhir. Silakan login kembali.",
                        },
                    }),
                );
            } else {
                window.location.href = "/login";
            }
        }
        return Promise.reject(error);
    },
);
