export const usePdfExport = () => {
    const printDocument = ({ title = 'Documento', sections = [] } = {}) => {
        if (typeof window === 'undefined') return false;

        const content = sections.map((section) => `
            <section>
                <h2>${section.heading}</h2>
                <ul>${section.lines.map((line) => `<li>${line}</li>`).join('')}</ul>
            </section>
        `).join('');
        const printWindow = window.open('', '_blank', 'width=900,height=700');
        if (!printWindow) return false;

        printWindow.document.write(`<!doctype html><html><head><title>${title}</title><style>body{font-family:Arial,sans-serif;padding:32px;color:#102030}h1{margin-top:0}section{margin-top:24px}li{margin:8px 0}</style></head><body><h1>${title}</h1>${content}</body></html>`);
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();

        return true;
    };

    return { printDocument };
};
