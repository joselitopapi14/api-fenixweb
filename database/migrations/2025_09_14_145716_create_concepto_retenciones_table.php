<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConceptoRetencionesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('concepto_retenciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tipo_retencion_id')
                ->constrained('tipo_retenciones')
                ->onDelete('cascade');
            $table->string('name', 999);
            $table->decimal('percentage', 5, 2);
            $table->timestamps();
        });

        DB::table('concepto_retenciones')->insert([
            ['tipo_retencion_id' => 2, 'name' => 'Compras generales (declarantes)', 'percentage' => 2.50],
            ['tipo_retencion_id' => 2, 'name' => 'Compras generales (no declarantes)', 'percentage' => 3.50],
            ['tipo_retencion_id' => 2, 'name' => 'Compras con tarjeta débito o crédito', 'percentage' => 1.50],
            ['tipo_retencion_id' => 2, 'name' => 'Compras de bienes o productos agrícolas o pecuarios sin procesamiento industrial', 'percentage' => 1.50],
            ['tipo_retencion_id' => 2, 'name' => 'Compras de bienes o productos agrícolas o pecuarios con procesamiento industrial (declarantes)', 'percentage' => 1.50],
            ['tipo_retencion_id' => 2, 'name' => 'Compras de bienes o productos agrícolas o pecuarios con procesamiento industrial (no declarantes)', 'percentage' => 2.50],
            ['tipo_retencion_id' => 2, 'name' => 'Compras de café pergamino o cereza', 'percentage' => 0.50],
            ['tipo_retencion_id' => 2, 'name' => 'Compras de combustibles derivados del petróleo', 'percentage' => 0.10],
            ['tipo_retencion_id' => 2, 'name' => 'Adquisición de vehículos', 'percentage' => 1.00],
            ['tipo_retencion_id' => 2, 'name' => 'Compras de bienes raíces cuya destinación y uso sea vivienda de habitación (por las primeras 20.000 UVT)', 'percentage' => 1.00],
            ['tipo_retencion_id' => 2, 'name' => 'Compras de bienes raíces cuya destinación y uso sea vivienda de habitación (exceso de las primeras 20.000 UVT)', 'percentage' => 2.50],
            ['tipo_retencion_id' => 2, 'name' => 'Compras de bienes raíces cuya destinación y uso sea distinto a vivienda de habitación', 'percentage' => 2.50],
            ['tipo_retencion_id' => 2, 'name' => 'Enajenación de activos fijos de personas naturales no retenedoras', 'percentage' => 1.00],

            ['tipo_retencion_id' => 2, 'name' => 'Servicios generales (declarantes)', 'percentage' => 4.00],
            ['tipo_retencion_id' => 2, 'name' => 'Servicios generales (no declarantes)', 'percentage' => 6.00],
            ['tipo_retencion_id' => 2, 'name' => 'Servicios de hoteles y restaurantes', 'percentage' => 3.50],
            ['tipo_retencion_id' => 2, 'name' => 'Servicios de licenciamiento o derecho de uso de software (declarantes)', 'percentage' => 3.50],
            ['tipo_retencion_id' => 2, 'name' => 'Servicios de licenciamiento o derecho de uso de software (no declarantes)', 'percentage' => 10.00],
            ['tipo_retencion_id' => 2, 'name' => 'Arrendamiento de bienes muebles', 'percentage' => 4.00],
            ['tipo_retencion_id' => 2, 'name' => 'Arrendamiento de bienes inmuebles (declarantes y no declarantes)', 'percentage' => 3.50],

            // Tipo renta 3
            ['tipo_retencion_id' => 2, 'name' => 'Honorarios y comisiones (personas naturales no declarantes)', 'percentage' => 10.00],
            ['tipo_retencion_id' => 2, 'name' => 'Honorarios y comisiones (personas naturales y jurídicas que suscriban contratos por más de 3.300 UVT o que la sumatoria de pagos supere 3.300 UVT en el año gravable)', 'percentage' => 11.00],
            ['tipo_retencion_id' => 2, 'name' => 'Honorarios y comisiones (personas jurídicas)', 'percentage' => 11.00],
            ['tipo_retencion_id' => 2, 'name' => 'Emolumentos eclesiásticos (declarantes)', 'percentage' => 4.00],
            ['tipo_retencion_id' => 2, 'name' => 'Emolumentos eclesiásticos (no declarantes)', 'percentage' => 3.50],
            ['tipo_retencion_id' => 2, 'name' => 'Consultoría: licenciamiento o derecho de uso de software (declarantes)', 'percentage' => 3.50],
            ['tipo_retencion_id' => 2, 'name' => 'Consultoría: licenciamiento o derecho de uso de software (no declarantes)', 'percentage' => 10.00],
            ['tipo_retencion_id' => 2, 'name' => 'Consultoría: actividades de análisis, diseño, desarrollo, etc., en programas de informática y diseño de páginas web', 'percentage' => 3.50],
            ['tipo_retencion_id' => 2, 'name' => 'Consultoría y administración delegada (personas jurídicas)', 'percentage' => 11.00],
            ['tipo_retencion_id' => 2, 'name' => 'Consultoría y administración delegada (personas naturales no declarantes)', 'percentage' => 10.00],
            ['tipo_retencion_id' => 2, 'name' => 'Consultoría y administración delegada (personas naturales no declarantes, si cumple requisitos de 3.300 UVT)', 'percentage' => 11.00],
            ['tipo_retencion_id' => 2, 'name' => 'Contratos de consultoría de obras públicas con personas jurídicas (método factor multiplicador)', 'percentage' => 2.00],
            ['tipo_retencion_id' => 2, 'name' => 'Contratos de consultoría en ingeniería de proyectos de infraestructura y edificaciones (personas naturales o jurídicas, consorcios/uniones temporales, si ingresos superan 3.300 UVT)', 'percentage' => 6.00],
            ['tipo_retencion_id' => 2, 'name' => 'Contratos de consultoría en ingeniería de proyectos de infraestructura y edificaciones (personas naturales no obligadas a declarar renta)', 'percentage' => 10.00],
            ['tipo_retencion_id' => 2, 'name' => 'Servicios sísmicos para el sector de hidrocarburos (pagos a declarantes)', 'percentage' => 6.00],
            ['tipo_retencion_id' => 2, 'name' => 'Servicios sísmicos para el sector de hidrocarburos (pagos a no declarantes)', 'percentage' => 10.00],

            // Tipo renta 4 (tarifa progresiva)
            ['tipo_retencion_id' => 2, 'name' => '0 hasta 95 UVT ($0 – $4.730.905): 0% (No se practica retención)', 'percentage' => 0.00],
            ['tipo_retencion_id' => 2, 'name' => '>95 hasta 150 UVT (>$4.730.905 – $7.469.850): 19% sobre el excedente de 95 UVT', 'percentage' => 19.00],
            ['tipo_retencion_id' => 2, 'name' => '>150 hasta 360 UVT (>$7.469.850 – $17.927.640): 28% sobre el excedente de 150 UVT, más 10 UVT', 'percentage' => 28.00],
            ['tipo_retencion_id' => 2, 'name' => '>360 hasta 640 UVT (>$17.927.640 – $31.871.360): 33% sobre el excedente de 360 UVT, más 69 UVT', 'percentage' => 33.00],
            ['tipo_retencion_id' => 2, 'name' => '>640 hasta 945 UVT (>$31.871.360 – $47.060.055): 35% sobre el excedente de 640 UVT, más 162 UVT', 'percentage' => 35.00],
            ['tipo_retencion_id' => 2, 'name' => '>945 hasta 2.300 UVT (>$47.060.055 – $114.537.700): 37% sobre el excedente de 945 UVT, más 268 UVT', 'percentage' => 37.00],
            ['tipo_retencion_id' => 2, 'name' => '>2.300 UVT en adelante (>$114.537.700): 39% sobre el excedente de 2.300 UVT, más 770 UVT', 'percentage' => 39.00],

            // Tipo renta 5
            ['tipo_retencion_id' => 2, 'name' => 'Otros ingresos tributarios (declarantes)', 'percentage' => 2.50],
            ['tipo_retencion_id' => 2, 'name' => 'Otros ingresos tributarios (no declarantes)', 'percentage' => 3.50],
            ['tipo_retencion_id' => 2, 'name' => 'Intereses o rendimientos financieros en general', 'percentage' => 7.00],
            ['tipo_retencion_id' => 2, 'name' => 'Rendimientos financieros provenientes de títulos de renta fija', 'percentage' => 4.00],
            ['tipo_retencion_id' => 2, 'name' => 'Rendimientos financieros de títulos denominados en moneda extranjera', 'percentage' => 4.00],
            ['tipo_retencion_id' => 2, 'name' => 'Ingresos de operaciones con instrumentos financieros derivados', 'percentage' => 2.50],
            ['tipo_retencion_id' => 2, 'name' => 'Intereses de operaciones de crédito activas o de mutuo comercial', 'percentage' => 2.50],
            ['tipo_retencion_id' => 2, 'name' => 'Loterías, rifas, apuestas y similares', 'percentage' => 20.00],
            ['tipo_retencion_id' => 2, 'name' => 'Premios obtenidos por propietario de caballo o perro en concursos hípicos o similares', 'percentage' => 10.00],
            ['tipo_retencion_id' => 2, 'name' => 'Colocación independiente de juegos de suerte y azar (ingreso diario > 5 UVT)', 'percentage' => 3.00],
            ['tipo_retencion_id' => 2, 'name' => 'Indemnizaciones diferentes a salariales y las recibidas en procesos contra el Estado', 'percentage' => 20.00],
            ['tipo_retencion_id' => 2, 'name' => 'Contratos de construcción y urbanización', 'percentage' => 2.00],
            ['tipo_retencion_id' => 2, 'name' => 'Dividendos y participaciones gravables (no residentes, sociedades extranjeras)', 'percentage' => 33.00],
            ['tipo_retencion_id' => 2, 'name' => 'Dividendos y participaciones gravables (declarantes residentes, si > 1400 UVT)', 'percentage' => 20.00],
            ['tipo_retencion_id' => 2, 'name' => 'Dividendos y participaciones no gravables (no residentes, sociedades extranjeras)', 'percentage' => 20.00],
            ['tipo_retencion_id' => 2, 'name' => 'Dividendos y participaciones no gravables (personas naturales residentes, 0-1090 UVT)', 'percentage' => 0.00],
            ['tipo_retencion_id' => 2, 'name' => 'Dividendos y participaciones no gravables (personas naturales residentes, >1090 UVT)', 'percentage' => 15.00],
            ['tipo_retencion_id' => 2, 'name' => 'Estudios de mercado y encuestas de opinión (personas jurídicas, sociedades de hecho)', 'percentage' => 4.00],

            // Tipo renta 6
            ['tipo_retencion_id' => 2, 'name' => 'Intereses, comisiones, honorarios, regalías, arrendamientos, compensaciones por servicios personales, explotación de propiedad industrial o know-how, servicios técnicos o asistencia técnica, beneficios o regalías por propiedad literaria, artística y científica', 'percentage' => 20.00],
            ['tipo_retencion_id' => 2, 'name' => 'Consultorías, servicios técnicos y asistencia técnica, prestados por personas no residentes o no domiciliadas en Colombia', 'percentage' => 20.00],
            ['tipo_retencion_id' => 2, 'name' => 'Contratos "llave en mano" y otros contratos de obra material (ingreso de fuente nacional para el contratista)', 'percentage' => 1.00],
            ['tipo_retencion_id' => 2, 'name' => 'Arrendamiento de maquinaria para construcción, mantenimiento o reparación de obras civiles por constructores colombianos en licitaciones públicas internacionales', 'percentage' => 2.00],
            ['tipo_retencion_id' => 2, 'name' => 'Profesores extranjeros sin residencia en el país, contratados por períodos no superiores a 4 meses por instituciones de educación superior', 'percentage' => 7.00],
            ['tipo_retencion_id' => 2, 'name' => 'Pagos o abonos en cuenta por rendimientos financieros a no residentes o no domiciliados, de créditos obtenidos en el exterior por un año o más, o de intereses/costos financieros de contratos de leasing con empresas extranjeras', 'percentage' => 15.00],
            ['tipo_retencion_id' => 2, 'name' => 'Pagos o abonos en cuenta, originados en contratos de leasing sobre naves, helicópteros y/o aerodinos, así como sus partes que se celebren directamente o a través de compañías de leasing, con empresas extranjeras sin domicilio en Colombia', 'percentage' => 1.00],
            ['tipo_retencion_id' => 2, 'name' => 'Rendimientos financieros o intereses a no residentes o no domiciliados, de créditos o títulos de deuda, por 8 años o más, para financiar proyectos de infraestructura bajo Asociaciones Público-Privadas', 'percentage' => 5.00],
            ['tipo_retencion_id' => 2, 'name' => 'Primas cedidas por reaseguros realizadas a personas no residentes o no domiciliadas en el país', 'percentage' => 5.00],
            ['tipo_retencion_id' => 2, 'name' => 'Pagos o abonos en cuenta por concepto de administración o dirección de que trata el artículo 124 del Estatuto Tributario, realizados a personas no residentes o no domiciliadas en el país', 'percentage' => 1.00],
            ['tipo_retencion_id' => 2, 'name' => 'Pagos o abonos en cuenta a la casa matriz por gastos de administración o dirección a no residentes o no domiciliados en el país', 'percentage' => 33.00],
            ['tipo_retencion_id' => 2, 'name' => 'Servicios de transporte internacional prestados por empresas de transporte aéreo o marítimo sin domicilio en el país', 'percentage' => 5.00],
            ['tipo_retencion_id' => 2, 'name' => 'En otros casos no contemplados, diferentes de ganancias ocasionales', 'percentage' => 15.00],
            ['tipo_retencion_id' => 2, 'name' => 'Concepto de ganancia ocasional', 'percentage' => 10.00],

            ['tipo_retencion_id' => 1, 'name' => 'Servicios gravados con IVA', 'percentage' => 15.00],
            ['tipo_retencion_id' => 1, 'name' => 'Servicios gravados con IVA prestados por persona natural no residente o entidad extranjera', 'percentage' => 100.00],
            ['tipo_retencion_id' => 1, 'name' => 'Venta de bienes gravados con IVA', 'percentage' => 15.00],
            ['tipo_retencion_id' => 1, 'name' => 'Venta de aerodinos', 'percentage' => 100.00],
            ['tipo_retencion_id' => 1, 'name' => 'Venta de desperdicios y desechos de plomo', 'percentage' => 100.00],
            ['tipo_retencion_id' => 1, 'name' => 'Venta de chatarra clasificada en partidas arancelarias 72.04, 74.04 y 76.02', 'percentage' => 100.00],
            ['tipo_retencion_id' => 1, 'name' => 'Venta de chatarra clasificada en partidas arancelarias 72.04, 74.04 y 76.02 (efectuada por siderúrgicas a otras siderúrgicas o a terceros)', 'percentage' => 15.00],
            ['tipo_retencion_id' => 1, 'name' => 'Venta de tabaco en rama o sin elaborar y desperdicios de tabaco (nomenclatura arancelaria andina 24.01)', 'percentage' => 100.00],
            ['tipo_retencion_id' => 1, 'name' => 'Venta de tabaco en rama o sin elaborar y desperdicios de tabaco (nomenclatura arancelaria andina 24.01) (efectuada por empresas tabacaleras a otras tabacaleras o a terceros)', 'percentage' => 15.00],
            ['tipo_retencion_id' => 1, 'name' => 'Venta de papel o cartón para reciclar (residuos y desechos)', 'percentage' => 100.00],
            ['tipo_retencion_id' => 1, 'name' => 'Venta de residuos plásticos para reciclar (residuos y desechos)', 'percentage' => 100.00],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('concepto_retenciones');
    }
}
