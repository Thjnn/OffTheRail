// ---- Màn bán tại quầy: giỏ hàng nằm ở Alpine, quét mã không chờ server ----
document.addEventListener('alpine:init', () => {
    Alpine.data('pos', (catalog, maxPercent) => ({
        catalog,
        maxPercent,
        code: '',
        notice: '',
        category: null,
        picking: null,
        lines: [],
        discount: 0,
        method: 'cash',
        cash: null,
        phone: '',
        name: '',
        customer: null,
        qrUrl: null,
        busy: false,
        token: crypto.randomUUID(),

        init() {
            this.index();
        },

        index() {
            this.byCode = new Map();
            for (const v of this.catalog.variants) {
                if (v.barcode) this.byCode.set(String(v.barcode).toLowerCase(), v);
                if (v.sku) this.byCode.set(String(v.sku).toLowerCase(), v);
            }
        },

        money(v) {
            return new Intl.NumberFormat('vi-VN').format(v || 0) + 'đ';
        },

        // Máy quét gõ như bàn phím: đang không ở ô nhập nào thì đưa phím vào ô quét.
        refocus(e) {
            const t = e.target;
            if (this.picking || ['INPUT', 'TEXTAREA', 'SELECT'].includes(t.tagName)) return;
            if (e.key.length === 1 || e.key === 'Enter') this.$refs.scan.focus();
        },

        async scan() {
            const raw = this.code.trim();
            this.code = '';
            this.notice = '';
            if (!raw) return;

            let v = this.byCode.get(raw.toLowerCase());
            if (!v) {
                v = await this.$wire.lookup(raw);
                if (v) {
                    this.catalog.variants.push(v);
                    this.index();
                }
            }
            if (v) {
                this.add(v);
                return;
            }

            // Không phải mã: coi là chữ tìm kiếm trên lưới sản phẩm.
            const hits = this.products(raw);
            if (hits.length === 1) this.pick(hits[0]);
            else if (hits.length) this.search = raw;
            else this.notice = `Không tìm thấy mã “${raw}”.`;
        },

        search: '',

        products(q = this.search) {
            const needle = (q || '').toLowerCase();
            const seen = new Map();
            for (const v of this.catalog.variants) {
                if (this.category !== null && v.category_id !== this.category) continue;
                if (needle && !v.name.toLowerCase().includes(needle)) continue;
                const p = seen.get(v.product_id) ?? { id: v.product_id, name: v.name, image: v.image, price: v.price, stock: 0 };
                p.stock += v.stock;
                p.price = Math.min(p.price, v.price);
                seen.set(v.product_id, p);
            }
            return [...seen.values()];
        },

        pick(p) {
            this.search = '';
            const variants = this.catalog.variants.filter((v) => v.product_id === p.id);
            if (variants.length === 1) this.add(variants[0]);
            else this.picking = p;
        },

        colorsOf(p) {
            if (!p) return [];
            const groups = new Map();
            for (const v of this.catalog.variants.filter((v) => v.product_id === p.id)) {
                const g = groups.get(v.color) ?? { color: v.color, hex: v.hex, variants: [] };
                g.variants.push(v);
                groups.set(v.color, g);
            }
            return [...groups.values()];
        },

        add(v) {
            const line = this.lines.find((l) => l.id === v.id);
            const have = line ? line.qty : 0;
            if (have >= v.stock) {
                this.notice = `${v.name} (${[v.color, v.size].filter(Boolean).join(' / ')}) chỉ còn ${v.stock}.`;
                return this.$nextTick(() => this.$refs.scan.focus());
            }
            if (line) {
                line.qty++;
                line.flash = false;
                this.$nextTick(() => (line.flash = true));
            } else {
                this.lines.unshift({ ...v, qty: 1, flash: true });
            }
            this.qrUrl = null;
            this.$nextTick(() => this.$refs.scan.focus());
        },

        dec(line) {
            line.qty--;
            if (line.qty <= 0) this.lines = this.lines.filter((l) => l !== line);
            this.qrUrl = null;
        },

        subtotal() {
            return this.lines.reduce((s, l) => s + l.price * l.qty, 0);
        },

        total() {
            return Math.max(0, this.subtotal() - (this.discount || 0));
        },

        discountError() {
            const d = this.discount || 0;
            if (d < 0 || d > this.subtotal()) return 'Mức giảm không hợp lệ.';
            if (this.maxPercent !== null && d > Math.floor(this.subtotal() * this.maxPercent / 100)) return `Nhân viên chỉ được giảm tối đa ${this.maxPercent}%.`;
            return '';
        },

        change() {
            return (this.cash || 0) - this.total();
        },

        quickCash() {
            const t = this.total();
            const up = (step) => Math.ceil(t / step) * step;
            return [...new Set([t, up(50000), up(100000), up(500000)])].filter((v) => v > 0).slice(0, 4);
        },

        async showQr() {
            this.method = 'qr';
            this.qrUrl = await this.$wire.qr(this.total());
        },

        async lookupCustomer() {
            this.customer = this.phone.trim() ? await this.$wire.findCustomer(this.phone) : null;
            if (this.customer) this.name = this.customer.name;
        },

        canPay() {
            if (this.busy || !this.lines.length || this.discountError()) return false;
            return this.method === 'qr' || this.change() >= 0;
        },

        async pay() {
            if (!this.canPay()) return;
            this.busy = true;
            try {
                await this.$wire.checkout(
                    this.lines.map((l) => ({ id: l.id, qty: l.qty })),
                    this.discount || 0, this.method, this.method === 'cash' ? this.cash : null,
                    this.phone || null, this.name || null, this.token,
                );
            } finally {
                this.busy = false; // thành công thì trang đã chuyển sang hoá đơn
            }
        },

        reset() {
            Object.assign(this, { lines: [], discount: 0, cash: null, phone: '', name: '', customer: null, qrUrl: null, notice: '', token: crypto.randomUUID() });
            this.$refs.scan.focus();
        },
    }));
});
