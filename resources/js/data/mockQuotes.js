export const quoteStats = [
    { icon: 'request_quote', value: '148', label: 'Total Cotizaciones', meta: '+12%', tone: 'primary' },
    { icon: 'hourglass_empty', value: '24', label: 'Pendientes', meta: '4 activas', tone: 'tertiary' },
    { icon: 'check_circle', value: '112', label: 'Aprobadas', meta: '85% éxito', tone: 'success' },
    { icon: 'trending_up', value: 'L. 42.8k', label: 'Valor Estimado Mes', tone: 'highlight' },
];

export const quoteRows = [
    { id: '#QT-2024-882', ref: 'REF: Frenos Cerámicos', vehicle: 'Porsche 911 GT3', brand: 'Brembo Premium Series', date: 'Hoy, 10:45 AM', amount: 'L. 3,420.00', status: 'Approved', tone: 'approved' },
    { id: '#QT-2024-881', ref: 'REF: Sistema Suspensión', vehicle: 'BMW M4 Competition', brand: 'Bilstein Performance', date: 'Ayer, 16:20 PM', amount: 'L. 5,180.50', status: 'Pending', tone: 'pending' },
    { id: '#QT-2024-879', ref: 'REF: Llantas Aleación', vehicle: 'Audi RS6 Avant', brand: 'Vossen Hybrid Forged', date: '12 May, 2024', amount: 'L. 8,900.00', status: 'Rejected', tone: 'rejected' },
    { id: '#QT-2024-875', ref: 'REF: Kit Aero', vehicle: 'Mercedes-AMG GT', brand: 'Brabus Rocket Edition', date: '10 May, 2024', amount: 'L. 12,450.00', status: 'Approved', tone: 'approved' },
];

export const mobileQuotes = [
    { id: '#QT-88291', client: 'Taller Central Automotriz', parts: 'Bujías Iridium (x4)', total: 'L. 1,240.00', time: 'Hace 2 horas', status: 'Aprobado', tone: 'approved', avatars: true },
    { id: '#QT-88285', client: 'Importadora Los Andes', parts: 'Kit de Embrague LUK', total: 'L. 3,850.25', time: 'Ayer, 15:30', status: 'Pendiente', tone: 'pending' },
    { id: '#QT-88274', client: 'Mecánica Express', parts: 'Aceite Sintético 5W30', total: 'L. 890.00', time: '12 Jun 2024', status: 'Enviado', tone: 'sent' },
];

export const mockQuotes = [
    {
        id: '#QT-2024-882',
        client: 'Taller Central Automotriz',
        ref: 'REF: Frenos Ceramicos',
        vehicle: 'Porsche 911 GT3',
        brand: 'Brembo Premium Series',
        date: 'Hoy, 10:45 AM',
        amount: 3420,
        status: 'approved',
        archived: false,
        items: [
            { name: 'Pastillas ceramicas Brembo', sku: 'BRK-552', qty: 2, price: 450 },
            { name: 'Discos ventilados GT', sku: 'DSK-911', qty: 2, price: 1260 },
        ],
    },
    {
        id: '#QT-2024-881',
        client: 'Importadora Los Andes',
        ref: 'REF: Sistema Suspension',
        vehicle: 'BMW M4 Competition',
        brand: 'Bilstein Performance',
        date: 'Ayer, 16:20 PM',
        amount: 5180.5,
        status: 'pending',
        archived: false,
        items: [
            { name: 'Kit suspension Bilstein', sku: 'SUS-M4-771', qty: 1, price: 5180.5 },
        ],
    },
    {
        id: '#QT-2024-879',
        client: 'Mecanica Express',
        ref: 'REF: Llantas Aleacion',
        vehicle: 'Audi RS6 Avant',
        brand: 'Vossen Hybrid Forged',
        date: '12 May, 2024',
        amount: 8900,
        status: 'rejected',
        archived: false,
        items: [
            { name: 'Llantas Vossen 21 pulgadas', sku: 'WHL-RS6-21', qty: 4, price: 2225 },
        ],
    },
    {
        id: '#QT-2024-875',
        client: 'Luxury Motors SAC',
        ref: 'REF: Kit Aero',
        vehicle: 'Mercedes-AMG GT',
        brand: 'Brabus Rocket Edition',
        date: '10 May, 2024',
        amount: 12450,
        status: 'approved',
        archived: true,
        items: [
            { name: 'Kit aero Brabus', sku: 'AER-AMG-900', qty: 1, price: 12450 },
        ],
    },
];
