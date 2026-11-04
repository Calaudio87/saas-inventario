<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password');

        // =========================================================================
        // TENANT 1: FERRETERÍA INDUSTRIAL EL MARTILLO S.A.
        // =========================================================================
        $ferreteria = Tenant::create([
            'name' => 'Ferretería Industrial El Martillo S.A.',
            'slug' => 'ferreteria-el-martillo',
            'industry' => 'Ferretería y Construcción',
        ]);

        app(TenantContext::class)->set($ferreteria->id);

        $adminFerreteria = User::create([
            'tenant_id' => $ferreteria->id,
            'name' => 'Roberto Gómez (Jefe de Operaciones)',
            'email' => 'admin@elmartillo.cl',
            'role' => User::ROLE_ADMIN,
            'password' => $password,
        ]);

        $staffFerreteria = User::create([
            'tenant_id' => $ferreteria->id,
            'name' => 'Matías Soto (Encargado de Bodega)',
            'email' => 'bodega@elmartillo.cl',
            'role' => User::ROLE_STAFF,
            'password' => $password,
        ]);

        $catalogoFerreteria = [
            [
                'sku' => 'FER-CEM-001',
                'name' => 'Saco Cemento Especial 25kg Polpaico',
                'category' => 'Obra Gruesa',
                'description' => 'Cemento puzolánico de alta resistencia inicial para hormigones y morteros.',
                'price' => 4.90,
                'min_stock_alert' => 50,
                'movements' => [
                    ['type' => 'in',  'qty' => 300, 'days_ago' => 27, 'user' => $adminFerreteria, 'reason' => 'Recepción camión planta Polpaico', 'ref' => 'OC-2026-101'],
                    ['type' => 'out', 'qty' => 110, 'days_ago' => 21, 'user' => $staffFerreteria, 'reason' => 'Despacho Constructora Pacífico',   'ref' => 'GD-88410'],
                    ['type' => 'out', 'qty' => 85,  'days_ago' => 14, 'user' => $staffFerreteria, 'reason' => 'Despacho obra Condominio Los Pinos', 'ref' => 'GD-88455'],
                    ['type' => 'in',  'qty' => 150, 'days_ago' => 10, 'user' => $adminFerreteria, 'reason' => 'Reposición quincenal obra gruesa', 'ref' => 'OC-2026-142'],
                    ['type' => 'out', 'qty' => 140, 'days_ago' => 4,  'user' => $staffFerreteria, 'reason' => 'Venta mayorista contratista vial',  'ref' => 'FAC-19022'],
                ],
            ],
            [
                'sku' => 'FER-DIS-002',
                'name' => 'Disco de Corte Metal Inox 4.5" x 1.0mm Norton',
                'category' => 'Abrasivos',
                'description' => 'Disco ultra delgado para corte preciso de acero inoxidable y perfiles estructurales.',
                'price' => 1.50,
                'min_stock_alert' => 80,
                'movements' => [
                    ['type' => 'in',  'qty' => 500, 'days_ago' => 28, 'user' => $adminFerreteria, 'reason' => 'Compra caja maestra distribuidor Norton', 'ref' => 'OC-2026-098'],
                    ['type' => 'out', 'qty' => 160, 'days_ago' => 19, 'user' => $staffFerreteria, 'reason' => 'Venta maestranza metalmecánica Sur',      'ref' => 'FAC-18901'],
                    ['type' => 'out', 'qty' => 120, 'days_ago' => 11, 'user' => $staffFerreteria, 'reason' => 'Ventas acumuladas mesón mostrador',       'ref' => 'BOL-sem-41'],
                    ['type' => 'out', 'qty' => 95,  'days_ago' => 3,  'user' => $staffFerreteria, 'reason' => 'Pedido taller de estructuras metálicas',  'ref' => 'FAC-19044'],
                ],
            ],
            [
                'sku' => 'FER-TOR-003',
                'name' => 'Caja Tornillo Volcanita Drywall 6 x 1-1/4" (1000 uds)',
                'category' => 'Fijaciones',
                'description' => 'Tornillo fosfatado negro punta fina para tabiquería Metalcon y yeso-cartón.',
                'price' => 9.80,
                'min_stock_alert' => 20,
                'movements' => [
                    ['type' => 'in',  'qty' => 120, 'days_ago' => 26, 'user' => $adminFerreteria, 'reason' => 'Importación fijaciones Mamut',      'ref' => 'OC-2026-105'],
                    ['type' => 'out', 'qty' => 45,  'days_ago' => 17, 'user' => $staffFerreteria, 'reason' => 'Venta instaladores tabiquería',     'ref' => 'FAC-18934'],
                    ['type' => 'out', 'qty' => 38,  'days_ago' => 8,  'user' => $staffFerreteria, 'reason' => 'Despacho remodelación oficinas',    'ref' => 'GD-88502'],
                    ['type' => 'out', 'qty' => 22,  'days_ago' => 2,  'user' => $staffFerreteria, 'reason' => 'Venta mostrador contratistas',      'ref' => 'BOL-99312'],
                ], // Queda en 15 uds (Stock Bajo <= 20)
            ],
            [
                'sku' => 'FER-SOL-004',
                'name' => 'Electrodo Soldadura Punto Azul E6011 1/8" Indura (Bolsa 5kg)',
                'category' => 'Soldadura',
                'description' => 'Electrodo celulósico de alta penetración para aceros al carbono en toda posición.',
                'price' => 18.50,
                'min_stock_alert' => 25,
                'movements' => [
                    ['type' => 'in',  'qty' => 90, 'days_ago' => 25, 'user' => $adminFerreteria, 'reason' => 'Abastecimiento mensual Indura',    'ref' => 'OC-2026-109'],
                    ['type' => 'out', 'qty' => 32, 'days_ago' => 15, 'user' => $staffFerreteria, 'reason' => 'Venta empresa montaje industrial', 'ref' => 'FAC-18960'],
                    ['type' => 'out', 'qty' => 28, 'days_ago' => 6,  'user' => $staffFerreteria, 'reason' => 'Salida mostrador cerrajeros',      'ref' => 'BOL-99201'],
                ],
            ],
            [
                'sku' => 'FER-EPP-005',
                'name' => 'Guante Cabritilla Corto Forrado Soldador/Obra (Par)',
                'category' => 'Seguridad Industrial',
                'description' => 'Cuero cabritilla natural de primera selección con refuerzo en palma.',
                'price' => 3.90,
                'min_stock_alert' => 30,
                'movements' => [
                    ['type' => 'in',  'qty' => 140, 'days_ago' => 24, 'user' => $adminFerreteria, 'reason' => 'Recepción lote EPP certificados', 'ref' => 'OC-2026-112'],
                    ['type' => 'out', 'qty' => 50,  'days_ago' => 12, 'user' => $staffFerreteria, 'reason' => 'Dotación cuadrilla constructora', 'ref' => 'FAC-18988'],
                    ['type' => 'out', 'qty' => 35,  'days_ago' => 5,  'user' => $staffFerreteria, 'reason' => 'Venta mostrador contratistas',    'ref' => 'BOL-99250'],
                ],
            ],
            [
                'sku' => 'FER-MAK-006',
                'name' => 'Taladro Percutor Inalámbrico 18V LXT 1/2" Makita DHP482',
                'category' => 'Herramientas Eléctricas',
                'description' => 'Incluye 2 baterías 18V 3.0Ah, cargador rápido DC18RC y maletín de transporte.',
                'price' => 169.90,
                'min_stock_alert' => 5,
                'movements' => [
                    ['type' => 'in',  'qty' => 18, 'days_ago' => 26, 'user' => $adminFerreteria, 'reason' => 'Compra distribuidor oficial Makita', 'ref' => 'OC-2026-103'],
                    ['type' => 'out', 'qty' => 9,  'days_ago' => 13, 'user' => $staffFerreteria, 'reason' => 'Equipamiento empresa instaladora',   'ref' => 'FAC-18975'],
                    ['type' => 'out', 'qty' => 6,  'days_ago' => 3,  'user' => $staffFerreteria, 'reason' => 'Venta promoción mes del maestro',    'ref' => 'FAC-19049'],
                ], // Queda en 3 uds (Stock Bajo <= 5)
            ],
            [
                'sku' => 'FER-BOS-007',
                'name' => 'Esmeril Angular 4-1/2" 850W GWS 850 Profesional Bosch',
                'category' => 'Herramientas Eléctricas',
                'description' => 'Motor de 850W con cabezal plano para trabajos en espacios reducidos.',
                'price' => 54.90,
                'min_stock_alert' => 6,
                'movements' => [
                    ['type' => 'in',  'qty' => 25, 'days_ago' => 23, 'user' => $adminFerreteria, 'reason' => 'Ingreso línea Bosch Professional', 'ref' => 'OC-2026-118'],
                    ['type' => 'out', 'qty' => 11, 'days_ago' => 9,  'user' => $staffFerreteria, 'reason' => 'Venta taller metalúrgico',         'ref' => 'FAC-19001'],
                ],
            ],
            [
                'sku' => 'FER-PIN-008',
                'name' => 'Tinetón Látex Acrílico Extracubriente Blanco 4 Galones Ceresita',
                'category' => 'Pinturas y Químicos',
                'description' => 'Pintura al agua de bajo olor y alta lavabilidad para interiores y exteriores.',
                'price' => 48.00,
                'min_stock_alert' => 10,
                'movements' => [
                    ['type' => 'in',  'qty' => 40, 'days_ago' => 22, 'user' => $adminFerreteria, 'reason' => 'Abastecimiento temporada pintura', 'ref' => 'OC-2026-120'],
                    ['type' => 'out', 'qty' => 19, 'days_ago' => 7,  'user' => $staffFerreteria, 'reason' => 'Terminaciones edificio residencial', 'ref' => 'GD-88520'],
                ],
            ],
            [
                'sku' => 'FER-HID-009',
                'name' => 'Tubo PVC Hidráulico Presión PN10 32mm x 6m Vinilit',
                'category' => 'Gasfitería',
                'description' => 'Tubería para conducción de agua potable fría a presión con unión cementar.',
                'price' => 8.40,
                'min_stock_alert' => 25,
                'movements' => [
                    ['type' => 'in',  'qty' => 80, 'days_ago' => 20, 'user' => $adminFerreteria, 'reason' => 'Recepción tuberías Vinilit',    'ref' => 'OC-2026-125'],
                    ['type' => 'out', 'qty' => 34, 'days_ago' => 5,  'user' => $staffFerreteria, 'reason' => 'Proyecto red de riego parcela', 'ref' => 'FAC-19030'],
                ],
            ],
            [
                'sku' => 'FER-ELE-010',
                'name' => 'Rollo Cable Eléctrico EVA Libre de Halógenos 2.5mm² Azul (100m)',
                'category' => 'Electricidad',
                'description' => 'Conductor unipolar retardante a la llama y baja emisión de humos según norma SEC.',
                'price' => 42.50,
                'min_stock_alert' => 12,
                'movements' => [
                    ['type' => 'in',  'qty' => 35, 'days_ago' => 18, 'user' => $adminFerreteria, 'reason' => 'Compra conductores certificados SEC', 'ref' => 'OC-2026-130'],
                    ['type' => 'out', 'qty' => 16, 'days_ago' => 2,  'user' => $staffFerreteria, 'reason' => 'Instalación eléctrica local comercial', 'ref' => 'FAC-19055'],
                ],
            ],
        ];

        $this->seedTenantCatalog($ferreteria, $catalogoFerreteria);

        // =========================================================================
        // TENANT 2: FARMACIA & DROGUERÍA BIOSALUD LTDA.
        // =========================================================================
        $farmacia = Tenant::create([
            'name' => 'Farmacia & Droguería BioSalud Ltda.',
            'slug' => 'farmacia-biosalud',
            'industry' => 'Salud y Farmacia',
        ]);

        app(TenantContext::class)->set($farmacia->id);

        $adminFarmacia = User::create([
            'tenant_id' => $farmacia->id,
            'name' => 'Dra. Valentina Rojas (Directora Técnica QF)',
            'email' => 'admin@biosalud.cl',
            'role' => User::ROLE_ADMIN,
            'password' => $password,
        ]);

        $staffFarmacia = User::create([
            'tenant_id' => $farmacia->id,
            'name' => 'Camila Fuentes (Auxiliar de Farmacia)',
            'email' => 'dispensario@biosalud.cl',
            'role' => User::ROLE_STAFF,
            'password' => $password,
        ]);

        $catalogoFarmacia = [
            [
                'sku' => 'FAR-ANA-001',
                'name' => 'Paracetamol 500mg Comprimidos Bioequivalente (Caja x 16)',
                'category' => 'Analgésicos y Antipiréticos',
                'description' => 'Analgésico y antipirético de venta directa para alivio sintomático de dolor y fiebre.',
                'price' => 1.80,
                'min_stock_alert' => 60,
                'movements' => [
                    ['type' => 'in',  'qty' => 450, 'days_ago' => 28, 'user' => $adminFarmacia, 'reason' => 'Abastecimiento Laboratorio Chile Lote #L902', 'ref' => 'GUIA-DROG-501'],
                    ['type' => 'out', 'qty' => 150, 'days_ago' => 20, 'user' => $staffFarmacia, 'reason' => 'Dispensación mostrador semana 1',             'ref' => 'CIERRE-POS-01'],
                    ['type' => 'out', 'qty' => 135, 'days_ago' => 12, 'user' => $staffFarmacia, 'reason' => 'Dispensación mostrador semana 2',             'ref' => 'CIERRE-POS-02'],
                    ['type' => 'in',  'qty' => 200, 'days_ago' => 8,  'user' => $adminFarmacia, 'reason' => 'Reposición alta demanda estacional',          'ref' => 'GUIA-DROG-548'],
                    ['type' => 'out', 'qty' => 140, 'days_ago' => 2,  'user' => $staffFarmacia, 'reason' => 'Dispensación mostrador semana 3 y convenio',  'ref' => 'CIERRE-POS-03'],
                ],
            ],
            [
                'sku' => 'FAR-CAR-002',
                'name' => 'Losartán Potásico 50mg Recubiertos Bioequivalente (Caja x 30)',
                'category' => 'Cardiovascular',
                'description' => 'Antihipertensivo antagonista de los receptores de angiotensina II (ARA-II).',
                'price' => 4.50,
                'min_stock_alert' => 40,
                'movements' => [
                    ['type' => 'in',  'qty' => 300, 'days_ago' => 27, 'user' => $adminFarmacia, 'reason' => 'Recepción programa pacientes crónicos', 'ref' => 'GUIA-DROG-504'],
                    ['type' => 'out', 'qty' => 115, 'days_ago' => 18, 'user' => $staffFarmacia, 'reason' => 'Dispensación recetas crónicas mes',     'ref' => 'REC-CRON-110'],
                    ['type' => 'out', 'qty' => 98,  'days_ago' => 6,  'user' => $staffFarmacia, 'reason' => 'Dispensación convenio adulto mayor',    'ref' => 'REC-CRON-149'],
                ],
            ],
            [
                'sku' => 'FAR-MET-003',
                'name' => 'Metformina Clorhidrato 850mgLiberación Prolongada (Caja x 60)',
                'category' => 'Metabolismo y Diabetes',
                'description' => 'Hipoglucemiante oral biguanida para control de diabetes mellitus tipo 2.',
                'price' => 6.90,
                'min_stock_alert' => 35,
                'movements' => [
                    ['type' => 'in',  'qty' => 240, 'days_ago' => 26, 'user' => $adminFarmacia, 'reason' => 'Ingreso droguería central Lote #M441', 'ref' => 'GUIA-DROG-509'],
                    ['type' => 'out', 'qty' => 95,  'days_ago' => 15, 'user' => $staffFarmacia, 'reason' => 'Dispensación pacientes crónicos',      'ref' => 'REC-CRON-122'],
                    ['type' => 'out', 'qty' => 82,  'days_ago' => 4,  'user' => $staffFarmacia, 'reason' => 'Ventas mostrador con receta médica',   'ref' => 'CIERRE-POS-04'],
                ],
            ],
            [
                'sku' => 'FAR-ANT-004',
                'name' => 'Amoxicilina + Ácido Clavulánico 875/125mg (Caja x 14 Comprimidos)',
                'category' => 'Antibióticos',
                'description' => 'Antibiótico betalactámico de amplio espectro. Venta bajo receta médica retenida/simple.',
                'price' => 11.90,
                'min_stock_alert' => 25,
                'movements' => [
                    ['type' => 'in',  'qty' => 160, 'days_ago' => 25, 'user' => $adminFarmacia, 'reason' => 'Recepción antibióticos campaña invierno', 'ref' => 'GUIA-DROG-512'],
                    ['type' => 'out', 'qty' => 78,  'days_ago' => 14, 'user' => $staffFarmacia, 'reason' => 'Dispensación bajo receta médica',         'ref' => 'REG-ANT-301'],
                    ['type' => 'out', 'qty' => 64,  'days_ago' => 3,  'user' => $staffFarmacia, 'reason' => 'Dispensación urgencias respiratorias',    'ref' => 'REG-ANT-342'],
                ], // Queda en 18 uds (Stock Bajo <= 25)
            ],
            [
                'sku' => 'FAR-RES-005',
                'name' => 'Inhalador Salbutamol 100mcg/dosis Aerosol 200 Dosis',
                'category' => 'Respiratorio',
                'description' => 'Broncodilatador agonista beta-2 adrenérgico selectivo de acción corta.',
                'price' => 5.40,
                'min_stock_alert' => 30,
                'movements' => [
                    ['type' => 'in',  'qty' => 150, 'days_ago' => 24, 'user' => $adminFarmacia, 'reason' => 'Compra inhaladores GSK/Lab Chile',   'ref' => 'GUIA-DROG-518'],
                    ['type' => 'out', 'qty' => 65,  'days_ago' => 11, 'user' => $staffFarmacia, 'reason' => 'Ventas broncopulmonar pediátrico',   'ref' => 'CIERRE-POS-05'],
                    ['type' => 'out', 'qty' => 62,  'days_ago' => 1,  'user' => $staffFarmacia, 'reason' => 'Alta rotación contingencia ambiental', 'ref' => 'CIERRE-POS-09'],
                ], // Queda en 23 uds (Stock Bajo <= 30)
            ],
            [
                'sku' => 'FAR-GAS-006',
                'name' => 'Omeprazol 20mg Cápsulas con Microgránulos Gastrorresistentes (x 30)',
                'category' => 'Gastroenterología',
                'description' => 'Inhibidor de la bomba de protones para reflujo gastroesofágico y úlcera péptica.',
                'price' => 3.60,
                'min_stock_alert' => 30,
                'movements' => [
                    ['type' => 'in',  'qty' => 180, 'days_ago' => 23, 'user' => $adminFarmacia, 'reason' => 'Abastecimiento mensual gastroenterología', 'ref' => 'GUIA-DROG-521'],
                    ['type' => 'out', 'qty' => 92,  'days_ago' => 7,  'user' => $staffFarmacia, 'reason' => 'Dispensaciones acumuladas quincena',       'ref' => 'CIERRE-POS-06'],
                ],
            ],
            [
                'sku' => 'FAR-AIN-007',
                'name' => 'Ibuprofeno 400mg Cápsulas Blandas de Rápida Absorción (Caja x 20)',
                'category' => 'Antiinflamatorios',
                'description' => 'Antiinflamatorio no esteroideo (AINE) para dolores musculares, dentales y cefaleas.',
                'price' => 3.20,
                'min_stock_alert' => 35,
                'movements' => [
                    ['type' => 'in',  'qty' => 200, 'days_ago' => 22, 'user' => $adminFarmacia, 'reason' => 'Recepción lote cápsulas blandas', 'ref' => 'GUIA-DROG-525'],
                    ['type' => 'out', 'qty' => 105, 'days_ago' => 9,  'user' => $staffFarmacia, 'reason' => 'Ventas mostrador turno diurno',   'ref' => 'CIERRE-POS-07'],
                ],
            ],
            [
                'sku' => 'FAR-ALL-008',
                'name' => 'Desloratadina 5mg Comprimidos Recubiertos Antialérgico (Caja x 30)',
                'category' => 'Antialérgicos',
                'description' => 'Antihistamínico de segunda generación sin efecto sedante para rinitis alérgica.',
                'price' => 5.90,
                'min_stock_alert' => 20,
                'movements' => [
                    ['type' => 'in',  'qty' => 120, 'days_ago' => 21, 'user' => $adminFarmacia, 'reason' => 'Stock temporada polinización primavera', 'ref' => 'GUIA-DROG-530'],
                    ['type' => 'out', 'qty' => 68,  'days_ago' => 5,  'user' => $staffFarmacia, 'reason' => 'Dispensación alérgicos estacionales',    'ref' => 'CIERRE-POS-08'],
                ],
            ],
            [
                'sku' => 'FAR-INS-009',
                'name' => 'Caja Guantes de Examen Nitrilo Sin Polvo Talla M (100 uds)',
                'category' => 'Insumos Clínicos',
                'description' => 'Guantes ambidiestros hipoalergénicos texturizados para procedimientos clínicos.',
                'price' => 7.50,
                'min_stock_alert' => 15,
                'movements' => [
                    ['type' => 'in',  'qty' => 90, 'days_ago' => 19, 'user' => $adminFarmacia, 'reason' => 'Compra insumos médicos certificados', 'ref' => 'GUIA-DROG-535'],
                    ['type' => 'out', 'qty' => 44, 'days_ago' => 4,  'user' => $staffFarmacia, 'reason' => 'Venta centro odontológico vecino',    'ref' => 'FAC-BIO-812'],
                ],
            ],
            [
                'sku' => 'FAR-EQP-010',
                'name' => 'Tensiómetro Digital de Brazo Automático Omron HEM-7120',
                'category' => 'Equipos Médicos',
                'description' => 'Monitor de presión arterial clínicamente validado con detector de latidos irregulares.',
                'price' => 44.90,
                'min_stock_alert' => 5,
                'movements' => [
                    ['type' => 'in',  'qty' => 20, 'days_ago' => 20, 'user' => $adminFarmacia, 'reason' => 'Recepción distribuidor Omron Healthcare', 'ref' => 'GUIA-DROG-540'],
                    ['type' => 'out', 'qty' => 11, 'days_ago' => 6,  'user' => $staffFarmacia, 'reason' => 'Ventas pacientes control hipertensión',   'ref' => 'CIERRE-POS-10'],
                ],
            ],
        ];

        $this->seedTenantCatalog($farmacia, $catalogoFarmacia);

        app(TenantContext::class)->set(null);
    }

    private function seedTenantCatalog(Tenant $tenant, array $catalog): void
    {
        foreach ($catalog as $item) {
            $product = Product::create([
                'tenant_id' => $tenant->id,
                'sku' => $item['sku'],
                'name' => $item['name'],
                'category' => $item['category'],
                'description' => $item['description'],
                'price' => $item['price'],
                'min_stock_alert' => $item['min_stock_alert'],
            ]);

            foreach ($item['movements'] as $mov) {
                $timestamp = now()->subDays($mov['days_ago'])->setTime(rand(8, 19), rand(0, 59));

                StockMovement::create([
                    'tenant_id' => $tenant->id,
                    'product_id' => $product->id,
                    'user_id' => $mov['user']->id,
                    'type' => $mov['type'],
                    'quantity' => $mov['qty'],
                    'reason' => $mov['reason'],
                    'reference' => $mov['ref'],
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);
            }
        }
    }
}
