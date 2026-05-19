<?php

namespace App\Data\Portal;

class PortalMockData
{
    /**
     * Temporary backend fixture data only.
     *
     * Real portal data belongs to the external platform and must be fetched
     * through a gateway, not persisted or owned by this Laravel app.
     */
    public static function dashboard(): array
    {
        return [
            'hero' => [
                'title' => 'Panel Principal',
                'message' => 'Bienvenido de nuevo, Roberto. El rendimiento de su cuenta ha subido un 12% este mes.',
                'market_status' => 'Bullish',
            ],
            'kpis' => [
                ['key' => 'available_balance', 'label' => 'Available Balance', 'value' => 'L. 142,850.00', 'trend' => '+2.4%', 'tone' => 'primary'],
                ['key' => 'pending_invoices', 'label' => 'Pending Invoices', 'value' => 'L. 24,115.50', 'meta' => '12 New', 'tone' => 'tertiary'],
                ['key' => 'active_promotions', 'label' => 'Active Promotions', 'value' => '15% OFF', 'description' => 'Selected engine components and premium brake systems. Valid until end of month.', 'tone' => 'secondary'],
            ],
            'sales_by_brand' => [
                ['label' => 'BMW', 'height' => 60, 'tone' => 'primary'],
                ['label' => 'MB', 'height' => 80, 'tone' => 'primary'],
                ['label' => 'AUDI', 'height' => 45, 'tone' => 'tertiary'],
                ['label' => 'LEX', 'height' => 35, 'tone' => 'primary'],
                ['label' => 'PORS', 'height' => 65, 'tone' => 'primary'],
            ],
            'activities' => [
                ['icon' => 'shopping_cart', 'tone' => 'primary', 'title' => 'Pedido #88241 Recibido', 'time' => 'Hace 2m', 'text' => 'Se ha procesado el pago para 15 kits de frenos cerámicos.', 'status' => 'Procesado'],
                ['icon' => 'description', 'tone' => 'tertiary', 'title' => 'Cotización Aprobada', 'time' => 'Hace 4h', 'text' => 'El cliente Taller Premium ha aceptado la oferta Q-0012.', 'status' => 'Aprobado'],
                ['icon' => 'priority_high', 'tone' => 'error', 'title' => 'Alerta de Inventario', 'time' => 'Ayer', 'text' => 'Stock crítico para transmisiones BMW Serie 3.', 'status' => 'Acción Requerida'],
            ],
        ];
    }

    public static function catalog(): array
    {
        $products = [
            ['tag' => 'NUEVO', 'brand' => 'BOSCH', 'category' => 'Inyección Diésel', 'id' => '#DX-921', 'name' => 'Bomba de Inyección Common Rail G3S', 'price' => 'L. 2,450.00', 'price_value' => 2450, 'image' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuB40luKAfIHkuX9cuzVAPdJ-t6SlzVjaJRx4_fdqDqWislagq9H4heK982QHP6aj6a1TvGocgf5TULsLxczMDkDqeK_wn8_4ltRQElwmaVUMreSYP2gACKCrlPfHwcuikXO7w6jR2-J6B34Inhjf3YfW0sunScx_tizB6iqKLl_-rK-NdMSh2Ty_g3lC4yuAlNe_GEZfAycqB9VpaoOQLM1ka5NZejUQ-IKFc_SOEreDcFdHB0cJ5NHSbsEPQy5zr4CORr4wYtVbSc'],
            ['brand' => 'HOLSET', 'category' => 'Turbocompresores', 'id' => '#HT-405', 'name' => 'Turbocompresor HE400VG Variable', 'price' => 'L. 1,890.00', 'price_value' => 1890, 'image' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuAt0snvcBxR7TV0SI9ztGPNTnMe2J5bW_HM5qMSpq1ewO0Hvifn1cQoKi92iYCDXtDtQMeK3E2Y8nUB_40X2orn8BuIrWUUvtWB0E51VJIdG7BAdgkXMeeN6AwHbN2cB779a9xlIRjbHQDGxJQ9UWYKPX_O2ty76BsxZFjjuYNfr0LyChJxNSiD8MM33nTGqQt2tqxhYrB2xWsamCH1kOMra2BLdUA4T0Rdf4mkurTwa0nzvrSY-Pe2HU6WVs78TmOanQRe33zGOuY'],
            ['tag' => 'MÁS VENDIDO', 'accent' => 'tertiary', 'brand' => 'SIEMENS', 'category' => 'Electrónica', 'id' => '#EV-300', 'name' => 'Controlador de Motor Trifásico E-Drive', 'price' => 'L. 4,120.00', 'price_value' => 4120, 'image' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuBt7Vu7Ik_EUVK2wKwPTYPJZuPJw_vIt1ECfmJPedMVOp_jGvxXCXOcWCzZcL3tXQt1RYF5qD0FhL-JED_m9OT1nK4OlbmcyETVf3budFUj5YSckinxnXHAnXm-WkkuRals4C6DClP_zaY3-H_lSbHu7k7zyTF0dHHnjAXD8brRkVWqe-nFQUn4Kb4kZ_EO8qTNsrG9CP18y4I29yxboRqjcMSQU3CLEM2S1H3A591eDj6SbZU8-13pVZOqvttilAjiBiZQkOv-M2Y'],
            ['brand' => 'DENSO', 'category' => 'Inyección Diésel', 'id' => '#DN-551', 'name' => 'Inyectores Piezoeléctricos Pro-Flow', 'price' => 'L. 850.00 /und', 'price_value' => 850, 'image' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuDIV0O0uNrXVp8mM3bMlEmFXET3Kv0GUYuIJLzvvgRIQtK-b92CHjHe5K6j0PFMI1DQZu6FXtHkSlZ-aHvsyjY56JAztS4fwtWLfmbN1gAhCDRKMwo7ABmynos_j4RiHcw529rpJCm-g43dCXHQhFjemsqpbxg8Qa1OeBoww3lmCQo-Pa5wwuVJKehdJnbm9b4czlcDBOippmJJoLc4ubmOhazEl56UYm6KK_3s_UyCTmjAV9ThRPlfPUbaoiJxemypm5ktv3L9kwc'],
            ['brand' => 'ALLISON', 'category' => 'Transmisión', 'id' => '#TR-4500', 'name' => 'Módulo de Transmisión Automática 4500', 'price' => 'L. 7,600.00', 'price_value' => 7600, 'image' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuCiKz84TJ4AGevMzeiimbHEO-D3Z8k-S7QTY085rWhroLkm_HF8ouD11_TDw4TgAIYOQTb8cm1CHrXWcN3rA37jzCm1iAOU6Gvnvjl7XQDN0VmlJR5ufw2klb84agz4cHW4WKkhxphREIAqm6Nf7S5Vy5No_wP1QNWvTKtb1CK_F_GlhozpXd-2wT4HjF9j0Tctr4zFUwq6xICYB7SKmX6hLyyo9gQHyojX8Zb73O9XURB4UTHm6qOqZ3Y3AUF3P3DIaa3_4CJZFlw'],
            ['brand' => 'CUMMINS', 'category' => 'Electrónica', 'id' => '#EC-800', 'name' => 'Unidad de Control Electrónico CM2350', 'price' => 'L. 1,250.00', 'price_value' => 1250, 'image' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuAhx1JSRS2srCslwIrNJ5bGSzloFdTahGoGMnzvqT0GxDbyJHSv_xGg-a4jEkrRnDLxDPw0whVSmd4fkakm-Z-ZAnOONCE3Ous0IM31LolAEVhoBB3BVEvbob9yqA-i3FJCVnz3vs54guzdmskk0IsKDsENqlIshJHGPgLClMEq4Bt5RVtVK08HNHbBKFDWPwsdw45ILTMWwbKA9GpmoJpGfnHYc2Ami3TVMnKx_Jp3ULWgp81hvNPaDTijTg3yD1W6sOFIxMnYw9k'],
            ['brand' => 'CAT', 'category' => 'Motores Completos', 'id' => '#MC-340', 'name' => 'Motor Completo C9 Reacondicionado', 'price' => 'L. 9,850.00', 'price_value' => 9850, 'image' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuB40luKAfIHkuX9cuzVAPdJ-t6SlzVjaJRx4_fdqDqWislagq9H4heK982QHP6aj6a1TvGocgf5TULsLxczMDkDqeK_wn8_4ltRQElwmaVUMreSYP2gACKCrlPfHwcuikXO7w6jR2-J6B34Inhjf3YfW0sunScx_tizB6iqKLl_-rK-NdMSh2Ty_g3lC4yuAlNe_GEZfAycqB9VpaoOQLM1ka5NZejUQ-IKFc_SOEreDcFdHB0cJ5NHSbsEPQy5zr4CORr4wYtVbSc'],
            ['brand' => 'BOSCH', 'category' => 'Electrónica', 'id' => '#SE-124', 'name' => 'Sensor de Presión Rail Industrial', 'price' => 'L. 320.00', 'price_value' => 320, 'image' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuAsL7v3K3LqyIpE4KUkZi77XYmQb-sGDSbCNNMC4RoPxluyHJEwii5U-SJy-EdinfWh7ArxiXqyLGezBmp0Wk-f_cv5IRuHqFqswOfXCyxL5sSyc0hlLFAX-pkBW1Cpb4Fa94DILY8hkRoMw7gRIJprfyd29IcT9SNt6eTJ_nclrxaP_0k66_1Rwcpt4EAQQCjCpMuXoL2aKCMzoE5ddFpPCg8LXdjrhC9gkBxq_PZ2FUEfhO9JvmHa7g8uA9h7xQXWeLpWahY1kSw'],
        ];

        foreach ($products as $index => &$product) {
            $stock = [18, 7, 0, 24, 3, 12, 0, 31][$index] ?? null;
            $product['stock'] = $stock;
            $product['availableQty'] = $stock;
            $product['available_qty'] = $stock;
            $product['isAvailable'] = $stock > 0;
            $product['is_available'] = $stock > 0;
            $product['stockLabel'] = $stock > 0 ? 'Disponible: '.$stock.' unidades' : 'Consultar disponibilidad';
            $product['stock_label'] = $product['stockLabel'];
            $product['lastStockUpdate'] = 'Mock temporal';
            $product['last_stock_update'] = 'Mock temporal';
        }
        unset($product);

        return [
            'products' => $products,
            'filters' => [
                'categories' => array_values(array_unique(array_column($products, 'category'))),
                'brands' => array_values(array_unique(array_column($products, 'brand'))),
                'min_price' => min(array_column($products, 'price_value')),
                'max_price' => 10000,
                'availability' => ['all', 'available', 'unavailable'],
            ],
        ];
    }

    public static function brands(): array
    {
        return [
            'brands' => [
                ['name' => 'Bosch', 'country' => 'Alemania', 'flag' => '#FFCE00', 'image' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuA1LQTQSQa3MuBoK3Hh7xxn4YYL_YN1WO-cV_pZ_yTEMQIozJprVKsQJH0oWBK3UNItY8fL9lKC7hMQA7ALJdnhLV6bgaI9g5lr12qiDeNCm8zbZcCLsjuM3vg6Bz22UrlOPYJMNhDkwaqF82nlKYwqowhr29DAksxkf92R9tHwqB6truQMhhCX_5n9LMPwSY5xOYyrds3beXOLsYtMalcHAURKieyONtXjkNwZ7BKWCWo2xfrK-ThAV8ucIOSE-_sDrHOf-DjSAXg', 'text' => 'Líder mundial en sistemas de inyección, electrónica y frenado.', 'tags' => ['INYECCIÓN', 'FRENOS']],
                ['name' => 'NGK', 'country' => 'Japón', 'flag' => '#BC002D', 'image' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuDBIj026ICutxbFS_ryfpuvWpqsX9dgH9FhPi4T_5hmgs6BigSAAgb6l0C0PricUae0kH3JFQYuQmomDBTC4yXoWpiK8ZBjk6WmCnF1wEyzVcJDy1MJpI7QTrxtVkOuuGHRbuT7e46rhkC5M3jd4ZBaJ7lRyQgGCcKefKHQ_ZGF4aRO3EYBzrEePDVpCDOcKkFuSaCKJly_Tc30JhuixY22hb2MxMFWeviuydARICED6Sud-P2B4bpb0XasnQETi5EnlTb8MIMhBVM', 'text' => 'Especialistas en bujías y sensores de oxígeno.', 'tags' => ['ENCENDIDO', 'SENSORES']],
                ['name' => 'Monroe', 'country' => 'USA', 'flag' => '#00205B', 'image' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuC8OTyZcTVYK3nq5P_UWxAw9V3cpDjDaNsPcMYQT-NujZi-kViriCdOZajh0kQWRIPZDpIuNJHhaYbiJQmZ5upWhkxJ7k6_BYw0sr66FD9akuQPUj61lb9sIZw0d6RskH1n5KJsZiPFEiULPTQqsH6_C73wIsGMzCUiW6JQRJzDnxmJ553_THC2G9ORb-nISHHopT7MIrXAvKKV08E4C4CGFe07JOe-PQ5Jyc2iRyTW_Ln-1PTppYYbLCPhTSrVEFsxC399qS2IKqo', 'text' => 'Amortiguadores de clase mundial para estabilidad y confort.', 'tags' => ['SUSPENSIÓN', 'ESTABILIDAD']],
            ],
            'stats' => [
                ['icon' => 'verified', 'label' => 'Garantía', 'value' => '100% Original'],
                ['icon' => 'local_shipping', 'label' => 'Distribución', 'value' => 'Nacional Directa'],
                ['icon' => 'inventory', 'label' => 'Stock', 'value' => '+50,000 SKUs'],
                ['icon' => 'handshake', 'label' => 'Partners', 'value' => 'Nivel Platinum'],
            ],
        ];
    }

    public static function orders(): array
    {
        return [
            'active_order' => [
                'id' => '#SM-92841-B',
                'status' => 'En Tránsito',
                'estimated_date' => '24 Oct, 2023',
                'priority' => 'Lvl-4 Logistics Priority',
                'steps' => [
                    ['label' => 'Procesado', 'meta' => '12 Oct, 14:20', 'done' => true],
                    ['label' => 'Enviado', 'meta' => '15 Oct, 09:45', 'done' => true],
                    ['label' => 'En Tránsito', 'meta' => 'En camino a Hub Central', 'active' => true],
                    ['label' => 'Entrega', 'meta' => 'Pendiente', 'muted' => true],
                ],
                'items' => [
                    ['name' => 'Turbocharger Garret GT35', 'sku' => 'SM-AUD-991', 'price' => 'L. 1,240.00', 'qty' => '01', 'image' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuBTr9M-xv3idpl8BnAmnTLBcjXkYVpBbR9KC4pIHtKk_OmDd2foRlVkFq4kMcSdEEaGfVilTJnB14pDbbjQP7QYbpoLs-7ibGkTZvwMykwyc0m7uWb8VR_9OBDVdI2xHTA2ZZuQumiBdkxEWq-9t7YXc0i8bxFSIotv_8PxkMKC0FB7RqG1IuC4wgeiIFuWzdpJt5fOkf-dpv2wsuHp9eI-xK7iUxDCs7l9WEcKZzntTfQK8e8zJ-gHfNBdhDkD3fnvQdSE5Myrgq0'],
                    ['name' => 'Brembo Ceramic Pads Set', 'sku' => 'SM-BRK-552', 'price' => 'L. 450.00', 'qty' => '02', 'image' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuBDsl2EVYwocivQAWJ3wjcFWwnGDfEUQ9h-6zJflTviX8oh5PF4nq-T1isB1bhkkcVvLANi14fgdxOzx0qLTVPQYYEh-eEMNwvfCyHdgcOzwg7ZB9JtWKnWxmRGoLDAZhnGQwtdRPPxM7ydN8g5P6Jp08t89cnjvudWBUauF7V8OqsF8CmQSBfjNKZ-z0R2iuyC_ZeU05v2jMkq2wMV6ncLi_9ixdfOVlIdAdA8qOddVsBW6bG5xhW-MHdMp0KwMpQ__S-fKuqDSiI'],
                ],
                'mobile_events' => [
                    ['title' => 'En centro de distribución regional', 'place' => 'Santiago de Chile - 10:45 AM', 'text' => 'El pedido ha ingresado a la bodega principal para la ruta final de despacho.', 'active' => true],
                    ['title' => 'Salida de almacén central', 'place' => 'Puerto Valparaíso - 06:12 AM'],
                    ['title' => 'Orden Confirmada', 'place' => 'Sistema B2B - Ayer, 04:30 PM'],
                ],
                'mobile_items' => [
                    ['icon' => 'minor_crash', 'name' => 'Kit de Frenos Cerámicos', 'meta' => 'Cod: PR-10293 - Cant: 2', 'price' => 'L. 240.00'],
                    ['icon' => 'directions_car', 'name' => 'Filtros de Aire Premium', 'meta' => 'Cod: FL-8821 - Cant: 12', 'price' => 'L. 185.50'],
                ],
            ],
        ];
    }

    public static function account(): array
    {
        return [
            'overview' => ['debt_total' => 'L. 42,850.00', 'credit_available' => 'L. 15,150.00', 'credit_trend' => '+12% vs mes anterior', 'credit_used' => '75% Utilizado', 'last_payment' => 'L. 5,200.00', 'last_payment_date' => '12 Oct, 2023', 'next_due' => '14 Nov', 'next_due_text' => 'Vencimiento en 5 días'],
            'credit_usage' => [
                ['label' => 'Categoría: Repuestos Motor', 'value' => 85, 'tone' => 'primary'],
                ['label' => 'Categoría: Suspensión', 'value' => 42, 'tone' => 'primary'],
                ['label' => 'Categoría: Accesorios', 'value' => 12, 'tone' => 'tertiary'],
            ],
            'chart' => ['months' => ['MAY', 'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV'], 'bars' => [40, 60, 85, 30, 55, 95, 70]],
            'movements' => [
                ['id' => '#FAC-2023-892', 'date' => '22 Oct, 2023', 'title' => 'Lote de Frenos Cerámicos', 'type' => 'Importación Directa', 'amount' => 'L. 12,450.00', 'status' => 'PENDIENTE', 'danger' => true],
                ['id' => '#FAC-2023-881', 'date' => '15 Oct, 2023', 'title' => 'Aceite Sintético 5W-30 (50u)', 'type' => 'Inventario Local', 'amount' => 'L. 3,120.00', 'status' => 'PAGADO'],
                ['id' => '#FAC-2023-875', 'date' => '10 Oct, 2023', 'title' => 'Kits de Distribución Premium', 'type' => 'Importación Directa', 'amount' => 'L. 22,280.00', 'status' => 'VENCIDO', 'danger' => true],
                ['id' => '#FAC-2023-864', 'date' => '02 Oct, 2023', 'title' => 'Filtros Premium Alto Flujo', 'type' => 'Inventario Local', 'amount' => 'L. 1,980.00', 'status' => 'PAGADO'],
                ['id' => '#PAY-2023-211', 'date' => '28 Sep, 2023', 'title' => 'Pago Recibido - Transferencia', 'type' => 'Banco Industrial', 'amount' => '+L. 5,200.00', 'status' => 'APLICADO'],
            ],
            'filters' => ['Todos', 'PENDIENTE', 'PAGADO', 'VENCIDO', 'APLICADO'],
            'periods' => ['2023', '2022'],
        ];
    }

    public static function invoices(): array
    {
        return [
            'summary' => [
                ['icon' => 'receipt_long', 'label' => 'Facturas 2024', 'value' => '42', 'trend' => '+8 este mes', 'tone' => 'cyan'],
                ['icon' => 'pending_actions', 'label' => 'Pendiente', 'value' => 'L. 21,350', 'trend' => '3 por vencer', 'tone' => 'amber'],
                ['icon' => 'verified', 'label' => 'Pagadas', 'value' => '31', 'trend' => '74% cerradas', 'tone' => 'emerald'],
            ],
            'invoices' => [
                ['number' => 'FAC-2024-1029', 'date' => '15 May, 2024', 'due' => '14 Jun, 2024', 'status' => 'Pendiente', 'tone' => 'pending', 'total' => 'L. 12,450.00', 'seller' => 'Carla Mendez'],
                ['number' => 'FAC-2024-1014', 'date' => '02 May, 2024', 'due' => '01 Jun, 2024', 'status' => 'Pagada', 'tone' => 'paid', 'total' => 'L. 3,120.00', 'seller' => 'Diego Salvatierra'],
                ['number' => 'FAC-2024-0988', 'date' => '22 Abr, 2024', 'due' => '22 May, 2024', 'status' => 'Vencida', 'tone' => 'overdue', 'total' => 'L. 8,900.00', 'seller' => 'Carla Mendez'],
                ['number' => 'FAC-2024-0941', 'date' => '04 Abr, 2024', 'due' => '04 May, 2024', 'status' => 'Pagada', 'tone' => 'paid', 'total' => 'L. 5,680.50', 'seller' => 'Roberto Silva'],
            ],
        ];
    }

    public static function quotes(): array
    {
        return [
            'stats' => [
                ['icon' => 'request_quote', 'value' => '148', 'label' => 'Total Cotizaciones', 'meta' => '+12%', 'tone' => 'primary'],
                ['icon' => 'hourglass_empty', 'value' => '24', 'label' => 'Pendientes', 'meta' => '4 activas', 'tone' => 'tertiary'],
                ['icon' => 'check_circle', 'value' => '112', 'label' => 'Aprobadas', 'meta' => '85% éxito', 'tone' => 'success'],
                ['icon' => 'trending_up', 'value' => 'L. 42.8k', 'label' => 'Valor Estimado Mes', 'tone' => 'highlight'],
            ],
            'quotes' => [
                ['id' => '#QT-2024-882', 'client' => 'Taller Central Automotriz', 'ref' => 'REF: Frenos Ceramicos', 'vehicle' => 'Porsche 911 GT3', 'brand' => 'Brembo Premium Series', 'date' => 'Hoy, 10:45 AM', 'amount' => 3420, 'status' => 'approved', 'archived' => false, 'items' => [['name' => 'Pastillas ceramicas Brembo', 'sku' => 'BRK-552', 'qty' => 2, 'price' => 450]]],
                ['id' => '#QT-2024-881', 'client' => 'Importadora Los Andes', 'ref' => 'REF: Sistema Suspension', 'vehicle' => 'BMW M4 Competition', 'brand' => 'Bilstein Performance', 'date' => 'Ayer, 16:20 PM', 'amount' => 5180.5, 'status' => 'pending', 'archived' => false, 'items' => [['name' => 'Kit suspension Bilstein', 'sku' => 'SUS-M4-771', 'qty' => 1, 'price' => 5180.5]]],
                ['id' => '#QT-2024-879', 'client' => 'Mecanica Express', 'ref' => 'REF: Llantas Aleacion', 'vehicle' => 'Audi RS6 Avant', 'brand' => 'Vossen Hybrid Forged', 'date' => '12 May, 2024', 'amount' => 8900, 'status' => 'rejected', 'archived' => false, 'items' => [['name' => 'Llantas Vossen 21 pulgadas', 'sku' => 'WHL-RS6-21', 'qty' => 4, 'price' => 2225]]],
                ['id' => '#QT-2024-875', 'client' => 'Luxury Motors SAC', 'ref' => 'REF: Kit Aero', 'vehicle' => 'Mercedes-AMG GT', 'brand' => 'Brabus Rocket Edition', 'date' => '10 May, 2024', 'amount' => 12450, 'status' => 'approved', 'archived' => true, 'items' => [['name' => 'Kit aero Brabus', 'sku' => 'AER-AMG-900', 'qty' => 1, 'price' => 12450]]],
            ],
        ];
    }

    public static function profile(): array
    {
        return [
            'user' => ['name' => 'Roberto Martinez', 'avatar' => ''],
            'fields' => [
                ['icon' => 'badge', 'label' => 'Código Cliente', 'value' => 'SM-B2B-00482'],
                ['icon' => 'business', 'label' => 'Empresa', 'value' => 'Taller Mecánico Especializado S.A.'],
                ['icon' => 'mail', 'label' => 'Correo', 'value' => 'roberto.martinez@tmesa.com'],
                ['icon' => 'call', 'label' => 'Teléfono', 'value' => '+51 987 654 210'],
                ['icon' => 'location_on', 'label' => 'Dirección', 'value' => 'Av. Los Próceres 1245, Chorrillos, Lima'],
                ['icon' => 'verified_user', 'label' => 'Condición', 'value' => 'Crédito 30 días'],
            ],
            'activity' => [
                ['icon' => 'request_quote', 'title' => 'Cotización aprobada', 'text' => 'Q-2024-882 por frenos cerámicos premium.', 'time' => 'Hace 2h', 'tone' => 'primary'],
                ['icon' => 'inventory_2', 'title' => 'Pedido en tránsito', 'text' => 'SM-92841-B rumbo a hub regional.', 'time' => 'Ayer', 'tone' => 'tertiary'],
                ['icon' => 'lock_reset', 'title' => 'Seguridad actualizada', 'text' => 'Verificación de dos pasos confirmada.', 'time' => '12 May', 'tone' => 'primary'],
            ],
        ];
    }
}
