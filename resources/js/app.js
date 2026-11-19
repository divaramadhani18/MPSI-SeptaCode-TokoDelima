import './bootstrap';

const successToast = document.querySelector('[data-success-toast]');

if (successToast instanceof HTMLElement) {
    const dismissToast = () => successToast.remove();
    const closeButton = successToast.querySelector('[data-success-toast-close]');

    closeButton?.addEventListener('click', dismissToast);
    window.setTimeout(dismissToast, 4000);
}
