// Alpine.js ships with Livewire 4; these are the shared UI behaviours used by the Blade components.
document.addEventListener('alpine:init', () => {
    /**
     * Positions a floating panel (dropdown menu / select list) with `position: fixed`
     * so it is never clipped by `overflow` containers such as scrollable tables.
     */
    const floating = {
        open: false,
        style: '',

        position(matchWidth = false) {
            const trigger = this.$refs.trigger;
            const panel = this.$refs.panel;

            if (!trigger || !panel) return;

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

            this.style = `top:${top}px;left:${left}px;${matchWidth ? `width:${rect.width}px;` : ''}`;
        },

        bindGlobalListeners() {
            this._onScroll = (event) => {
                if (this.open && !this.$refs.panel?.contains(event.target)) this.close();
            };
            this._onResize = () => this.open && this.close();

            window.addEventListener('scroll', this._onScroll, true);
            window.addEventListener('resize', this._onResize);
        },

        unbindGlobalListeners() {
            window.removeEventListener('scroll', this._onScroll, true);
            window.removeEventListener('resize', this._onResize);
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
