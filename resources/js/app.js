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

    document.querySelectorAll('[data-category-toggle]').forEach((button) => {
        const targetSelector = button.getAttribute('data-category-toggle');

        if (!targetSelector) {
            return;
        }

        const target = document.querySelector(targetSelector);
        const icon = button.querySelector('[data-category-toggle-icon]');

        if (!target) {
            return;
        }

        button.addEventListener('click', () => {
            const isExpanded = button.getAttribute('aria-expanded') === 'true';
            button.setAttribute('aria-expanded', isExpanded ? 'false' : 'true');
            target.classList.toggle('hidden', isExpanded);

            if (icon) {
                icon.textContent = isExpanded ? '+' : '-';
            }
        });
    });

    document.querySelectorAll('[data-dependent-categories]').forEach((wrapper) => {
        const parentSelect = wrapper.querySelector('[data-parent-category-select]');
        const subcategorySelect = wrapper.querySelector('[data-subcategory-select]');
        const rawCategoryMap = wrapper.getAttribute('data-category-map') || '{}';
        const selectedSubcategoryId = String(wrapper.getAttribute('data-selected-subcategory') || '');

        if (!parentSelect || !subcategorySelect) {
            return;
        }

        let categoryMap = {};

        try {
            categoryMap = JSON.parse(rawCategoryMap);
        } catch {
            categoryMap = {};
        }

        const initialParentValue = String(parentSelect.value || '');
        let activeSubcategoryId = selectedSubcategoryId || String(subcategorySelect.value || '');

        const renderSubcategories = () => {
            const parentId = String(parentSelect.value || '');
            const options = Array.isArray(categoryMap[parentId]) ? categoryMap[parentId] : [];
            const previousValue = activeSubcategoryId;

            subcategorySelect.innerHTML = '';

            const placeholderOption = document.createElement('option');
            placeholderOption.value = '';
            placeholderOption.textContent = options.length ? 'Select a subcategory...' : 'No subcategories available';
            subcategorySelect.appendChild(placeholderOption);

            options.forEach((subcategory) => {
                const option = document.createElement('option');
                option.value = String(subcategory.id);
                option.textContent = String(subcategory.name || '');
                subcategorySelect.appendChild(option);
            });

            if (previousValue && options.some((subcategory) => String(subcategory.id) === previousValue)) {
                subcategorySelect.value = previousValue;
            } else {
                subcategorySelect.value = '';
            }

            activeSubcategoryId = String(subcategorySelect.value || '');
            subcategorySelect.disabled = options.length === 0;
        };

        parentSelect.addEventListener('change', () => {
            if (String(parentSelect.value || '') !== initialParentValue) {
                activeSubcategoryId = '';
            }

            renderSubcategories();
        });

        subcategorySelect.addEventListener('change', () => {
            activeSubcategoryId = String(subcategorySelect.value || '');
        });

        renderSubcategories();
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

            const min = Math.max(1, Number(target.getAttribute('min') || 1));
            const max = Number(target.getAttribute('max') || 0);
            const current = Number(target.value || min);
            let next = button.dataset.qtyToggle === 'minus' ? current - 1 : current + 1;

            next = Math.max(min, next);

            if (max > 0) {
                next = Math.min(max, next);
            }

            target.value = next;
            target.dispatchEvent(new Event('change', { bubbles: true }));
        });
    });

    document.querySelectorAll('[data-account-type-select]').forEach((select) => {
        const form = select.closest('form');
        const sellerFields = form?.querySelector('[data-seller-fields]');

        if (!sellerFields) {
            return;
        }

        const toggleSellerFields = () => {
            const isSeller = select.value === 'seller';
            sellerFields.hidden = !isSeller;
        };

        select.addEventListener('change', toggleSellerFields);
        toggleSellerFields();
    });

    document.querySelectorAll('[data-cart-quantity-form]').forEach((form) => {
        const input = form.querySelector('[data-cart-quantity-input]');
        const warningSelector = form.getAttribute('data-warning-target') || '';
        const warning = warningSelector ? document.querySelector(warningSelector) : null;

        if (!input) {
            return;
        }

        const formatCurrency = (amount) => new Intl.NumberFormat('en-US').format(Math.round(Number(amount || 0)));
        let lastSyncedQuantity = Number(input.value || 1);

        const showWarning = (message) => {
            if (!warning) {
                return;
            }

            warning.textContent = message;
            warning.classList.remove('hidden');
        };

        const hideWarning = () => {
            if (!warning) {
                return;
            }

            warning.textContent = '';
            warning.classList.add('hidden');
        };

        const syncQuantity = debounce(async () => {
            const stockQuantity = Number(input.getAttribute('data-stock-quantity') || 0);
            const min = Math.max(1, Number(input.getAttribute('min') || 1));
            const max = Number(input.getAttribute('max') || 0);
            let quantity = Number(input.value || min);

            if (Number.isNaN(quantity) || quantity < min) {
                quantity = min;
            }

            if (stockQuantity < 1) {
                showWarning('This product is now out of stock.');
                input.value = String(lastSyncedQuantity);

                return;
            }

            if (stockQuantity > 0 && quantity > stockQuantity) {
                quantity = stockQuantity;
                input.value = String(quantity);
                showWarning(`Only ${stockQuantity} item(s) are available in stock.`);
            }

            if (max > 0) {
                quantity = Math.min(quantity, max);
                input.value = String(quantity);
            }

            if (quantity === lastSyncedQuantity) {
                return;
            }

            const formData = new FormData(form);
            formData.set('quantity', String(quantity));

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: formData,
                    credentials: 'same-origin',
                });

                const payload = await response.json().catch(() => ({}));

                if (!response.ok) {
                    const message = payload?.errors?.quantity?.[0] || payload?.message || 'Unable to update cart quantity.';
                    showWarning(message);

                    if (typeof payload?.stock_quantity === 'number' && payload.stock_quantity > 0) {
                        const fallback = Math.max(1, payload.stock_quantity);
                        input.value = String(fallback);
                    } else {
                        input.value = String(lastSyncedQuantity);
                    }

                    return;
                }

                lastSyncedQuantity = Number(payload.item_quantity || quantity);
                input.value = String(lastSyncedQuantity);
                hideWarning();

                const itemTotalElement = document.querySelector(`[data-cart-item-total="${payload.item_id}"]`);
                if (itemTotalElement) {
                    itemTotalElement.textContent = `Tk ${formatCurrency(payload.item_total)}`;
                }

                const subtotalElement = document.querySelector('[data-cart-subtotal]');
                if (subtotalElement) {
                    subtotalElement.textContent = `Tk ${formatCurrency(payload.subtotal)}`;
                }

                const itemsCountElement = document.querySelector('[data-cart-items-count]');
                if (itemsCountElement) {
                    itemsCountElement.textContent = String(payload.items_count ?? itemsCountElement.textContent);
                }
            } catch {
                showWarning('Network error while updating cart. Please try again.');
                input.value = String(lastSyncedQuantity);
            }
        }, 300);

        input.addEventListener('input', syncQuantity);
        input.addEventListener('change', syncQuantity);
    });

    document.querySelectorAll('[data-payout-auto-refresh]').forEach((wrapper) => {
        const interval = Number(wrapper.getAttribute('data-payout-refresh-interval') || 20000);

        if (!Number.isFinite(interval) || interval < 5000) {
            return;
        }

        window.setInterval(() => {
            const active = document.activeElement;
            const isEditing = !!active
                && wrapper.contains(active)
                && ['INPUT', 'TEXTAREA', 'SELECT'].includes(active.tagName);

            if (isEditing) {
                return;
            }

            window.location.reload();
        }, interval);
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
                    <img class="h-12 w-12 rounded-2xl object-cover" src="${item.image || '/images/placeholder.svg'}" alt="">
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

    document.querySelectorAll('[data-password-wrapper]').forEach((wrapper) => {
        const input = wrapper.querySelector('[data-password-input]');
        const toggle = wrapper.querySelector('[data-password-toggle]');

        if (!input || !toggle) {
            return;
        }

        const syncToggle = () => {
            const isVisible = input.type === 'text';
            toggle.textContent = isVisible ? 'Hide' : 'Show';
            toggle.setAttribute('aria-label', isVisible ? 'Hide password' : 'Show password');
            toggle.setAttribute('aria-pressed', isVisible ? 'true' : 'false');
        };

        syncToggle();

        toggle.addEventListener('click', () => {
            input.type = input.type === 'password' ? 'text' : 'password';
            syncToggle();
            input.focus();
        });
    });
});
