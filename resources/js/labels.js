// Trang in tem: vẽ Code128 cho mọi <svg data-barcode> rồi mở hộp in.
import JsBarcode from 'jsbarcode';

document.addEventListener('DOMContentLoaded', () => {
    const codes = document.querySelectorAll('svg[data-barcode]');
    codes.forEach((el) => JsBarcode(el, el.dataset.barcode, { format: 'CODE128', height: 40, displayValue: true, fontSize: 12, margin: 0 }));
    if (codes.length) window.print();
});
