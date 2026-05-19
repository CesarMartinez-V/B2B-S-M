export const invoiceSummary = [
    { icon: 'receipt_long', label: 'Facturas 2024', value: '42', trend: '+8 este mes', tone: 'cyan' },
    { icon: 'pending_actions', label: 'Pendiente', value: 'L. 21,350', trend: '3 por vencer', tone: 'amber' },
    { icon: 'verified', label: 'Pagadas', value: '31', trend: '74% cerradas', tone: 'emerald' },
];

export const invoices = [
    { number: 'FAC-2024-1029', date: '15 May, 2024', due: '14 Jun, 2024', status: 'Pendiente', tone: 'pending', total: 'L. 12,450.00', seller: 'Carla Mendez' },
    { number: 'FAC-2024-1014', date: '02 May, 2024', due: '01 Jun, 2024', status: 'Pagada', tone: 'paid', total: 'L. 3,120.00', seller: 'Diego Salvatierra' },
    { number: 'FAC-2024-0988', date: '22 Abr, 2024', due: '22 May, 2024', status: 'Vencida', tone: 'overdue', total: 'L. 8,900.00', seller: 'Carla Mendez' },
    { number: 'FAC-2024-0941', date: '04 Abr, 2024', due: '04 May, 2024', status: 'Pagada', tone: 'paid', total: 'L. 5,680.50', seller: 'Roberto Silva' },
];
