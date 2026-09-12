// Alpine.js ships with Livewire 4; these are the shared UI behaviours used by the Blade components.
document.addEventListener('alpine:init', () => {
    /**
     * Positions a floating panel (dropdown menu / select list) with `position: fixed`
     * so it is never clipped by `overflow` containers such as scrollable tables.
     */
    const floating = {
        open: false,
        style: '',
        matchWidth: false,

        position(matchWidth = this.matchWidth) {
            const trigger = this.$refs.trigger;
            const panel = this.$refs.panel;

            if (!trigger || !panel) return;

            this.matchWidth = matchWidth;

            const rect = trigger.getBoundingClientRect();
            const gap = 6;
            const margin = 8;

            if (matchWidth) panel.style.width = `${rect.width}px`;

            const width = panel.offsetWidth;
            const height = panel.offsetHeight;

            let left = matchWidth ? rect.left : rect.right - width;
            left = Math.max(margin, Math.min(left, window.innerWidth - width - margin));

            let top = rect.bottom + gap;
            if (top + height > window.innerHeight - margin && rect.top - height - gap > margin) {
                top = rect.top - height - gap;
            }

            // `position: fixed` is measured from the nearest ancestor with transform / filter /
            // backdrop-filter (e.g. the blurred header), not always from the viewport: compensate.
            const origin = this.fixedOrigin(panel);

            this.style = `top:${top - origin.top}px;left:${left - origin.left}px;${matchWidth ? `width:${rect.width}px;` : ''}`;
        },

        /**
         * Viewport coordinates of the point the panel's `top:0;left:0` resolves to.
         */
        fixedOrigin(panel) {
            // A sibling probe shares the panel's containing block but not its enter transition.
            const probe = document.createElement('div');
            probe.style.cssText = 'position:fixed;top:0;left:0;width:0;height:0;visibility:hidden;pointer-events:none;';
            panel.parentNode.insertBefore(probe, panel);

            const { top, left } = probe.getBoundingClientRect();

            probe.remove();

            return { top, left };
        },

        triggerIsVisible() {
            const rect = this.$refs.trigger?.getBoundingClientRect();

            return !!rect && rect.bottom > 0 && rect.top < window.innerHeight && rect.right > 0 && rect.left < window.innerWidth;
        },

        /**
         * Keep the panel anchored while the page scrolls or the viewport changes (a mobile keyboard
         * opening fires both); only close when the trigger itself has scrolled out of view.
         */
        bindGlobalListeners() {
            this._onScroll = (event) => {
                if (!this.open || this.$refs.panel?.contains(event.target)) return;

                this.triggerIsVisible() ? this.position() : this.close();
            };
            this._onResize = () => {
                if (!this.open) return;

                this.triggerIsVisible() ? this.position() : this.close();
            };

            window.addEventListener('scroll', this._onScroll, true);
            window.addEventListener('resize', this._onResize);
            window.visualViewport?.addEventListener('resize', this._onResize);
        },

        unbindGlobalListeners() {
            window.removeEventListener('scroll', this._onScroll, true);
            window.removeEventListener('resize', this._onResize);
            window.visualViewport?.removeEventListener('resize', this._onResize);
        },
    };

    Alpine.data('uiMenu', () => ({
        ...floating,

        init() {
            this.bindGlobalListeners();
        },

        destroy() {
            this.unbindGlobalListeners();
        },

        toggle() {
            this.open ? this.close() : this.openMenu();
        },

        openMenu() {
            this.open = true;
            this.$nextTick(() => this.position());
        },

        close() {
            this.open = false;
        },
    }));

    /**
     * Custom select / combobox bound to a Livewire property through `$wire.entangle`.
     * Options live in the `data-options` attribute so Livewire re-renders can update them.
     */
    Alpine.data('uiSelect', ({ value, placeholder = 'Pilih...', searchable = false, clearable = false }) => ({
        ...floating,
        value,
        placeholder,
        searchable,
        clearable,
        search: '',
        highlighted: -1,
        version: 0,

        init() {
            this.bindGlobalListeners();

            // Livewire morphs the attribute when the option list changes (e.g. dependent selects).
            this._observer = new MutationObserver(() => this.version++);
            this._observer.observe(this.$root, { attributes: true, attributeFilter: ['data-options'] });
        },

        destroy() {
            this.unbindGlobalListeners();
            this._observer?.disconnect();
        },

        get options() {
            this.version;

            try {
                return JSON.parse(this.$root.dataset.options || '[]');
            } catch {
                return [];
            }
        },

        get selected() {
            const current = String(this.value ?? '');

            return current === '' ? null : (this.options.find((option) => String(option.value) === current) ?? null);
        },

        get label() {
            return this.selected?.label ?? '';
        },

        get filtered() {
            const term = this.search.trim().toLowerCase();

            return term === '' ? this.options : this.options.filter((option) => option.label.toLowerCase().includes(term));
        },

        isSelected(option) {
            return String(option.value) === String(this.value ?? '');
        },

        toggle() {
            this.open ? this.close() : this.openMenu();
        },

        openMenu() {
            this.open = true;
            this.search = '';
            this.highlighted = Math.max(0, this.filtered.findIndex((option) => this.isSelected(option)));

            this.$nextTick(() => {
                this.position(true);
                this.searchable ? this.$refs.search?.focus() : this.$refs.panel?.focus();
                this.scrollToHighlighted();
            });
        },

        close(focusTrigger = false) {
            if (!this.open) return;

            this.open = false;
            this.search = '';

            if (focusTrigger) this.$refs.trigger?.focus();
        },

        select(option) {
            this.value = option.value;
            this.close(true);
        },

        clear() {
            this.value = '';
            this.close();
        },

        highlight(index) {
            this.highlighted = index;
        },

        move(step) {
            const count = this.filtered.length;

            if (count === 0) return;

            this.highlighted = (this.highlighted + step + count) % count;
            this.scrollToHighlighted();
        },

        scrollToHighlighted() {
            this.$nextTick(() => {
                this.$refs.list?.querySelector(`[data-index="${this.highlighted}"]`)?.scrollIntoView({ block: 'nearest' });
            });
        },

        onKeydown(event) {
            if (!this.open) {
                if (['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(event.key)) {
                    event.preventDefault();
                    this.openMenu();
                }

                return;
            }

            switch (event.key) {
                case 'ArrowDown':
                    event.preventDefault();
                    this.move(1);
                    break;
                case 'ArrowUp':
                    event.preventDefault();
                    this.move(-1);
                    break;
                case 'Home':
                    event.preventDefault();
                    this.highlighted = 0;
                    this.scrollToHighlighted();
                    break;
                case 'End':
                    event.preventDefault();
                    this.highlighted = this.filtered.length - 1;
                    this.scrollToHighlighted();
                    break;
                case 'Enter':
                    event.preventDefault();
                    if (this.filtered[this.highlighted]) this.select(this.filtered[this.highlighted]);
                    break;
                case 'Escape':
                    event.preventDefault();
                    this.close(true);
                    break;
                case 'Tab':
                    this.close();
                    break;
            }
        },
    }));
});

/**
 * Instant feedback for wire:navigate: the moment a visit starts, move the sidebar highlight and the
 * header title to the destination and swap the page body for the skeleton, so the app "moves" on
 * click and the server response only fills in the content.
 */
document.addEventListener('livewire:navigate', (event) => {
    if (event.detail.cached) return;

    const trimSlash = (pathname) => pathname.replace(/\/+$/, '') || '/';
    const path = trimSlash(event.detail.url.pathname);
    const links = [...document.querySelectorAll('[data-nav-link]')];

    const target = links
        .filter((link) => {
            const linkPath = trimSlash(new URL(link.href, window.location.href).pathname);

            return path === linkPath || path.startsWith(`${linkPath}/`);
        })
        .sort((a, b) => b.href.length - a.href.length)[0];

    if (target) {
        links.forEach((link) => link.removeAttribute('aria-current'));
        target.setAttribute('aria-current', 'page');

        const title = document.querySelector('[data-page-title]');
        if (title) title.textContent = target.textContent.trim();
    }

    document.documentElement.setAttribute('data-navigating', '');
});

document.addEventListener('livewire:navigated', () => {
    document.documentElement.removeAttribute('data-navigating');
});
