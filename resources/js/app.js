import './bootstrap';

const debounce = (callback, wait = 250) => {
    let timeoutId;

    return (...args) => {
        window.clearTimeout(timeoutId);
        timeoutId = window.setTimeout(() => callback(...args), wait);
    };
};

document.addEventListener('DOMContentLoaded', () => {
    const drawer = document.querySelector('[data-category-drawer]');

    document.querySelectorAll('[data-drawer-open]').forEach((button) => {
        button.addEventListener('click', () => {
            drawer?.classList.remove('hidden');
        });
    });

    document.querySelectorAll('[data-drawer-close]').forEach((button) => {
        button.addEventListener('click', () => {
            drawer?.classList.add('hidden');
        });
    });

    document.querySelectorAll('[data-flash-message]').forEach((message) => {
        window.setTimeout(() => {
            message.classList.add('opacity-0', 'translate-y-1');
            window.setTimeout(() => message.remove(), 250);
        }, 4200);
    });

    const syncGallery = (root) => {
        const mainImage = root.querySelector('[data-gallery-main]');
        const thumbs = root.querySelectorAll('[data-gallery-thumb]');

        thumbs.forEach((thumb) => {
            thumb.addEventListener('click', () => {
                if (!mainImage) {
                    return;
                }

                mainImage.setAttribute('src', thumb.dataset.galleryThumb || '');

                thumbs.forEach((item) => item.classList.remove('ring-2', 'ring-[var(--color-brand-rose)]'));
                thumb.classList.add('ring-2', 'ring-[var(--color-brand-rose)]');
            });
        });
    };

    document.querySelectorAll('[data-product-gallery]').forEach(syncGallery);

    document.querySelectorAll('[data-qty-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const target = document.querySelector(button.dataset.qtyTarget || '');

            if (!target) {
                return;
            }

            const current = Number(target.value || 1);
            const next = button.dataset.qtyToggle === 'minus' ? Math.max(1, current - 1) : current + 1;
            target.value = next;
        });
    });

    document.querySelectorAll('[data-search-box]').forEach((wrapper) => {
        const input = wrapper.querySelector('[data-search-input]');
        const results = wrapper.querySelector('[data-search-results]');

        if (!input || !results) {
            return;
        }

        const performSearch = debounce(async () => {
            const query = input.value.trim();

            if (query.length < 2) {
                results.classList.add('hidden');
                results.innerHTML = '';
                return;
            }

            const response = await fetch(`/search/suggestions?q=${encodeURIComponent(query)}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const items = await response.json();

            if (!items.length) {
                results.innerHTML = '<div class="px-4 py-3 text-sm text-slate-500">No products found.</div>';
                results.classList.remove('hidden');
                return;
            }

            results.innerHTML = items.map((item) => `
                <a class="flex items-center gap-3 px-4 py-3 transition hover:bg-[var(--color-brand-soft)]" href="${item.url}">
                    <img class="h-12 w-12 rounded-2xl object-cover" src="${item.image || 'https://picsum.photos/seed/search/80/80'}" alt="">
                    <div>
                        <div class="font-semibold text-slate-900">${item.name}</div>
                        <div class="text-sm text-[var(--color-brand-rose)]">Tk ${item.price}</div>
                    </div>
                </a>
            `).join('');
            results.classList.remove('hidden');
        }, 180);

        input.addEventListener('input', performSearch);
        input.addEventListener('focus', performSearch);
        document.addEventListener('click', (event) => {
            if (!wrapper.contains(event.target)) {
                results.classList.add('hidden');
            }
        });
    });
});
