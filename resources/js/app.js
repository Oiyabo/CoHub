import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

function createRepliesGroup() {
    const group = document.createElement('div');

    group.className = 'mt-2';
    group.dataset.repliesGroup = '';
    group.setAttribute('x-data', '{ open: true }');

    const button = document.createElement('button');

    button.type = 'button';
    button.className = 'text-[11px] text-slate-500 font-semibold cursor-pointer flex items-center gap-1';
    button.setAttribute('@click', 'open = !open');
    button.setAttribute(':aria-expanded', 'open');

    const arrow = document.createElement('span');
    arrow.setAttribute('x-text', "open ? '▼' : '▶'");

    const count = document.createElement('span');
    count.dataset.repliesCount = '';
    count.textContent = '1 Balasan';

    button.append(arrow, count);

    const list = document.createElement('div');

    list.className = 'mt-2';
    list.dataset.repliesList = '';
    list.setAttribute('x-show', 'open');

    group.append(button, list);

    return group;
}

document.addEventListener('submit', async function (event) {
    const form = event.target.closest('form[data-comment-ajax]');

    if (!form) {
        return;
    }

    event.preventDefault();

    const button = form.querySelector('button[type="submit"]');

    if (button) {
        button.disabled = true;
    }

    try {
        const csrfToken = document.querySelector(
            'meta[name="csrf-token"]'
        );

        const response = await fetch(form.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken.content,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new FormData(form),
            credentials: 'same-origin'
        });

        const data = await response.json();

        if (!response.ok) {
            const errors = data.errors
                ? Object.values(data.errors).flat().join('\n')
                : data.message || 'Terjadi kesalahan.';

            alert(errors);
            return;
        }

        const post = form.closest('[data-post-id]');

        if (form.dataset.commentAjax === 'store') {
            const commentsList = post.querySelector(
                '[data-comments-list]'
            );

            const template = document.createElement('template');
            template.innerHTML = data.html.trim();

            if (data.parent_id) {
                const parent = post.querySelector(
                    `[data-comment-id="${data.parent_id}"]`
                );

                const repliesContainer = parent.querySelector(
                    '[data-replies-container]'
                );

                let group = repliesContainer.querySelector(
                    ':scope > [data-replies-group]'
                );

                let isNewGroup = false;

                if (!group) {
                    group = createRepliesGroup();
                    repliesContainer.appendChild(group);
                    isNewGroup = true;
                }

                const repliesList = group.querySelector(
                    '[data-replies-list]'
                );

                repliesList.appendChild(template.content);

                if (isNewGroup) {
                    window.Alpine.initTree(group);
                } else {
                    window.Alpine.$data(group).open = true;
                }

                const replyCount = repliesList.children.length;

                group.querySelector(
                    '[data-replies-count]'
                ).textContent = `${replyCount} Balasan`;

            } else {
                const emptyMessage = commentsList.querySelector(
                    '[data-empty-comments]'
                );

                if (emptyMessage) {
                    emptyMessage.remove();
                }

                commentsList.appendChild(template.content);
            }

            const countElement = post.querySelector(
                '[data-comment-count]'
            );

            if (countElement) {
                const currentCount = parseInt(
                    countElement.dataset.count || '0',
                    10
                );

                const newCount = currentCount + 1;

                countElement.dataset.count = newCount;
                countElement.textContent = `💬 ${newCount} Komentar`;
            }

            form.reset();

            const details = form.closest('details');

            if (details) {
                details.open = false;
            }
        }

        if (
            form.dataset.commentAjax === 'update' ||
            form.dataset.commentAjax === 'delete'
        ) {
            const comment = form.closest('[data-comment-id]');

            const template = document.createElement('template');
            template.innerHTML = data.html.trim();

            const newBody = template.content.querySelector(
                '[data-comment-body]'
            );

            const oldBody = comment.querySelector(
                '[data-comment-body]'
            );

            if (newBody && oldBody) {
                oldBody.replaceWith(newBody);
            }
        }

    } catch (error) {
        alert('Gagal menghubungi server. Silakan coba lagi.');
        console.error(error);
    } finally {
        if (button) {
            button.disabled = false;
        }
    }
});