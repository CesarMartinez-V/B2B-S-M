export const creditUsage = [
    { label: 'Categoría: Repuestos Motor', value: 85, tone: 'primary' },
    { label: 'Categoría: Suspensión', value: 42, tone: 'primary' },
    { label: 'Categoría: Accesorios', value: 12, tone: 'tertiary' },
];

export const desktopTransactions = [
    { id: '#FAC-2023-892', date: '22 Oct, 2023', title: 'Lote de Frenos Cerámicos', type: 'Importación Directa', amount: 'L. 12,450.00', status: 'PENDIENTE', danger: true },
    { id: '#FAC-2023-881', date: '15 Oct, 2023', title: 'Aceite Sintético 5W-30 (50u)', type: 'Inventario Local', amount: 'L. 3,120.00', status: 'PAGADO' },
    { id: '#FAC-2023-875', date: '10 Oct, 2023', title: 'Kits de Distribución Premium', type: 'Importación Directa', amount: 'L. 22,280.00', status: 'VENCIDO', danger: true },
];

export const mobileTransactions = [
    { title: 'Factura #F-4902', meta: '05 Nov 2023 • Repuestos Suspensión', amount: '-L. 1,240.00', status: 'Pendiente', tone: 'primary', danger: true },
    { title: 'Pago Recibido - Transf', meta: '02 Nov 2023 • Banco Industrial', amount: '+L. 5,000.00', status: 'Aplicado', tone: 'tertiary' },
    { title: 'Factura #F-4855', meta: '28 Oct 2023 • Kit Distribución', amount: '-L. 850.50', status: 'Pagado', tone: 'primary' },
    { title: 'Nota de Crédito #NC-12', meta: '25 Oct 2023 • Devolución Filtros', amount: '+L. 210.00', status: 'Aplicado', tone: 'tertiary' },
    { title: 'Factura #F-4810', meta: '20 Oct 2023 • Aceite Motor 5W30', amount: '-L. 2,100.00', status: 'Vencido', tone: 'primary', danger: true },
];

export const accountChart = {
    months: ['MAY', 'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV'],
    bars: [40, 60, 85, 30, 55, 95, 70],
};
