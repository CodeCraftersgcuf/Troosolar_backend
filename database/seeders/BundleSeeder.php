<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Bundles;

class BundleSeeder extends Seeder
{
    private const FEATURED_IMAGE_URL = 'https://troosolar.hmstech.org/storage/products/d5c7f116-57ed-46ef-a659-337c94c308a9.png';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if bundles already exist
        if (Bundles::where('bundle_type', 'Inverter + Battery')->count() > 0 ||
            Bundles::where('bundle_type', 'Solar+Inverter+Battery')->count() > 0) {
            $this->command->info('Bundles already exist. Skipping...');
            return;
        }

        $whatBundlePowers = '6-10 LED bulbs (7-10 W each), 1 LED TV (32-43"), 1 Decoder (DSTV/GOtv), 1 standing or ceiling fan, 1 Laptop or desktop computer, 1 Wi-Fi router / modem, Small speakers.';

        // Specification tab structure (Yinergy/Cworth) – shared warranty/OEM
        $specBase = [
            'company_oem' => 'Yinergy/Cworth',
            'inverter_capacity_kva' => '1.2',
            'voltage' => '12V',
            'battery_type' => 'Lithium Ion Battery',
            'inverter_warranty' => '2 Years Warranty',
            'battery_warranty' => '5 Years Warranty',
        ];

        // Inverter + Battery Bundles (LitePower1213, LitePower1225, LitePower1238)
        $inverterBatteryBundles = [
            [
                'title' => 'LitePower1213',
                'bundle_type' => 'Inverter + Battery',
                'featured_image' => self::FEATURED_IMAGE_URL,
                'total_price' => 877366.63,
                'discount_price' => 0.00,
                'inver_rating' => '1.2',
                'total_output' => '1.3',
                'total_load' => null,
                'product_model' => 'OG-1P1K2-T - 1.2kVA Yinergy Inverter/GCL 12100 12V 1.3kWh Cworth Energy Lithium Ion Battery',
                'system_capacity_display' => '1.2kVA Inverter & 1.3kWh Lithium Ion Battery',
                'detailed_description' => 'This system consists of a 1.2kVA Inverter with a 1.3kWh Lithium Ion Battery capacity. It is capable of powering 6-10 LED bulbs (7-10 W each), one 32-43" LED TV, one DSTV/GOtv decoder, one standing or ceiling fan, one laptop or desktop computer, one Wi-Fi router/modem, and small speakers. The bundle is designed to supply power for 1 to 9 hours, depending on the connected load. Its backup time ensures power from sunset to sunrise, or during extremely rainy/cloudy weather when batteries are fully charged and grid power is insufficient or unavailable.',
                'what_is_inside_bundle_text' => "1 unit of 1.2kVA Inverter\n1 unit of 1.3kWh Lithium Ion Battery\nInstallation Materials\nAccessories",
                'what_bundle_powers_text' => $whatBundlePowers,
                'backup_time_description' => '1 hour to 9 hours, depending on load. The battery provides backup from sunset to sunrise or during inclement weather if fully charged and grid power is insufficient.',
                'specifications' => array_merge($specBase, [
                    'battery_capacity_kwh' => '1.3',
                    'solar_panel_capacity_w' => null,
                    'backup_time_range' => '1 to 9 hours',
                ]),
            ],
            [
                'title' => 'LitePower1225',
                'bundle_type' => 'Inverter + Battery',
                'featured_image' => self::FEATURED_IMAGE_URL,
                'total_price' => 1072385.06,
                'discount_price' => 0.00,
                'inver_rating' => '1.2',
                'total_output' => '2.5',
                'total_load' => null,
                'product_model' => 'OG-1P1K2-T - 1.2kVA Yinergy Inverter/GCL 12200 12V 2.5kWh Cworth Energy Lithium Ion Battery',
                'system_capacity_display' => '1.2kVA Inverter & 2.5kWh Lithium Ion Battery',
                'detailed_description' => 'This system features a 1.2kVA Inverter with a 2.5kWh Lithium Ion Battery capacity. It is designed to power 6-10 LED bulbs (7-10 W each), one 32-43" LED TV, one DSTV/GOtv decoder, one standing or ceiling fan, one laptop or desktop computer, one Wi-Fi router/modem, and small speakers. The bundle can supply power for 2 to 17 hours, depending on the connected load. Its backup time ensures power from sunset to sunrise, or during extremely rainy/cloudy weather when batteries are fully charged and grid power is insufficient or unavailable.',
                'what_is_inside_bundle_text' => "1 unit of 1.2kVA Inverter\n1 unit of 2.5kWh Lithium Ion Battery\nInstallation Materials\nAccessories",
                'what_bundle_powers_text' => $whatBundlePowers,
                'backup_time_description' => '2 hours to 17 hours, depending on load. The battery provides backup from sunset to sunrise or during inclement weather if fully charged and grid power is insufficient.',
                'specifications' => array_merge($specBase, [
                    'battery_capacity_kwh' => '2.5',
                    'solar_panel_capacity_w' => null,
                    'backup_time_range' => '2 to 17 hours',
                ]),
            ],
            [
                'title' => 'LitePower1238',
                'bundle_type' => 'Inverter + Battery',
                'featured_image' => self::FEATURED_IMAGE_URL,
                'total_price' => 1308817.88,
                'discount_price' => 0.00,
                'inver_rating' => '1.2',
                'total_output' => '3.8',
                'total_load' => null,
                'product_model' => 'OG-1P1K2-T - 1.2kVA Yinergy Inverter/GCL 12300 12V 3.8kWh Cworth Energy Lithium Ion Battery',
                'system_capacity_display' => '1.2kVA Inverter & 3.8kWh Lithium Ion Battery',
                'detailed_description' => 'This system includes a 1.2kVA Inverter with a 3.8kWh Lithium Ion Battery capacity. It can power 6-10 LED bulbs (7-10 W each), one 32-43" LED TV, one DSTV/GOtv decoder, one standing or ceiling fan, one laptop or desktop computer, one Wi-Fi router/modem, and small speakers. This bundle is designed to provide power for 3 to 25 hours, depending on the connected load. Its backup time ensures power from sunset to sunrise, or during extremely rainy/cloudy weather when batteries are fully charged and grid power is insufficient or unavailable.',
                'what_is_inside_bundle_text' => "1 unit of 1.2kVA Inverter\n1 unit of 3.8kWh Lithium Ion Battery\nInstallation Materials\nAccessories",
                'what_bundle_powers_text' => $whatBundlePowers,
                'backup_time_description' => '3 hours to 25 hours, depending on load. The battery provides backup from sunset to sunrise or during inclement weather if fully charged and grid power is insufficient.',
                'specifications' => array_merge($specBase, [
                    'battery_capacity_kwh' => '3.8',
                    'solar_panel_capacity_w' => null,
                    'backup_time_range' => '3 to 25 hours',
                ]),
            ],
        ];

        // Solar+Inverter+Battery Bundles (SolarLitePower1213, SolarLitePower1225, SolarLitePower1238)
        $solarInverterBatteryBundles = [
            [
                'title' => 'SolarLitePower1213',
                'bundle_type' => 'Solar+Inverter+Battery',
                'featured_image' => self::FEATURED_IMAGE_URL,
                'total_price' => 1097728.19,
                'discount_price' => 0.00,
                'inver_rating' => '1.2',
                'total_output' => '1.3',
                'total_load' => '0.6',
                'product_model' => '590Wp Jinko Solar Panel/OG-1P1K2-T - 1.2kVA Yinergy Inverter/GCL 12100 12V 1.3kWh Cworth Energy Lithium Ion Battery',
                'system_capacity_display' => '0.6kWp Solar Panel, 1.2kVA Inverter & 1.3kWh Lithium Ion Battery',
                'detailed_description' => 'This is a 600Wp Solar Panel Capacity with 1.2kVA Inverter Rating and 1.3kWh Lithium Ion Battery Storage. It powers 6-10 LED bulbs (7-10 W each), 1 LED TV (32-43"), 1 Decoder (DSTV/GOtv), 1 Standing or Ceiling Fan, 1 Laptop or Desktop Computer, 1 Wi-Fi Router / Modem and Small Speakers. The bundle will supply power between 1 hour to 9 hours depending on your load throughout the backup time. The back-up time is what the battery will provide from sunset to sunrise or in extremely rainy/cloudy weather when the batteries are fully charged and when solar input or power from the grid is insufficient or unavailable.',
                'what_is_inside_bundle_text' => "1 unit of 590W Solar Panel\n1 unit of 1.2kVA Inverter\n1 unit of 1.3kWh Lithium Ion Battery\nInstallation Materials\nAccessories",
                'what_bundle_powers_text' => $whatBundlePowers,
                'backup_time_description' => 'Between 1 hour to 9 hours depending on your load throughout the back up time. The back-up time is what the battery will provide from sunset to sunrise or in extremely rainy/cloudy weather when the batteries are fully charged and when solar input or power from the grid is insufficient or unavailable.',
                'specifications' => array_merge($specBase, [
                    'battery_capacity_kwh' => '1.3',
                    'solar_panel_capacity_w' => '600',
                    'backup_time_range' => '1 to 9 hours',
                ]),
            ],
            [
                'title' => 'SolarLitePower1225',
                'bundle_type' => 'Solar+Inverter+Battery',
                'featured_image' => self::FEATURED_IMAGE_URL,
                'total_price' => 1445732.56,
                'discount_price' => 0.00,
                'inver_rating' => '1.2',
                'total_output' => '2.5',
                'total_load' => '1.2',
                'product_model' => '1.2kWp Jinko Solar Panel/OG-1P1K2-T - 1.2kVA Yinergy Inverter/GCL 12200 12V 2.5kWh Cworth Energy Lithium Ion Battery',
                'system_capacity_display' => '1.2kWp Solar Panel, 1.2kVA Inverter & 2.5kWh Lithium Ion Battery',
                'detailed_description' => 'This is a 1.2kWp Solar Panel Capacity with 1.2kVA Inverter Rating and 2.5kWh Lithium Ion Battery Storage. It powers 6-10 LED bulbs (7-10 W each), 1 LED TV (32-43"), 1 Decoder (DSTV/GOtv), 1 Standing or Ceiling Fan, 1 Laptop or Desktop Computer, 1 Wi-Fi Router / Modem and Small Speakers. The bundle will supply power between 2 hours to 17 hours depending on your load throughout the backup time. The back-up time is what the battery will provide from sunset to sunrise or in extremely rainy/cloudy weather when the batteries are fully charged and when solar input or power from the grid is insufficient or unavailable.',
                'what_is_inside_bundle_text' => "2 units of 590W Solar Panels\n1 unit of 1.2kVA Inverter\n1 unit of 2.5kWh Lithium Ion Battery\nInstallation Materials\nAccessories",
                'what_bundle_powers_text' => $whatBundlePowers,
                'backup_time_description' => 'Between 2 hours to 17 hours depending on your load throughout the back up time. The back-up time is what the battery will provide from sunset to sunrise or in extremely rainy/cloudy weather when the batteries are fully charged and when solar input or power from the grid is insufficient or unavailable.',
                'specifications' => array_merge($specBase, [
                    'battery_capacity_kwh' => '2.5',
                    'solar_panel_capacity_w' => '1200',
                    'backup_time_range' => '2 to 17 hours',
                ]),
            ],
            [
                'title' => 'SolarLitePower1238',
                'bundle_type' => 'Solar+Inverter+Battery',
                'featured_image' => self::FEATURED_IMAGE_URL,
                'total_price' => 1530305.00,
                'discount_price' => 0.00,
                'inver_rating' => '1.2',
                'total_output' => '3.8',
                'total_load' => '1.2',
                'product_model' => '1.2kWp Jinko Solar Panel/OG-1P1K2-T - 1.2kVA Yinergy Inverter/GCL 12300 12V 3.8kWh Cworth Energy Lithium Ion Battery',
                'system_capacity_display' => '1.2kWp Solar Panel, 1.2kVA Inverter & 3.8kWh Lithium Ion Battery',
                'detailed_description' => 'This is a 1.2kWp Solar Panel Capacity with 1.2kVA Inverter Rating and 3.8kWh Lithium Ion Battery Storage. It powers 6-10 LED bulbs (7-10 W each), 1 LED TV (32-43"), 1 Decoder (DSTV/GOtv), 1 Standing or Ceiling Fan, 1 Laptop or Desktop Computer, 1 Wi-Fi Router / Modem and Small Speakers. The bundle will supply power between 3 hours to 25 hours depending on your load throughout the backup time. The back-up time is what the battery will provide from sunset to sunrise or in extremely rainy/cloudy weather when the batteries are fully charged and when solar input or power from the grid is insufficient or unavailable.',
                'what_is_inside_bundle_text' => "2 units of 590W Solar Panels\n1 unit of 1.2kVA Inverter\n1 unit of 3.8kWh Lithium Ion Battery\nInstallation Materials\nAccessories",
                'what_bundle_powers_text' => $whatBundlePowers,
                'backup_time_description' => 'Between 3 hours to 25 hours depending on your load throughout the back up time. The back-up time is what the battery will provide from sunset to sunrise or in extremely rainy/cloudy weather when the batteries are fully charged and when solar input or power from the grid is insufficient or unavailable.',
                'specifications' => array_merge($specBase, [
                    'battery_capacity_kwh' => '3.8',
                    'solar_panel_capacity_w' => '1200',
                    'backup_time_range' => '3 to 25 hours',
                ]),
            ],
        ];

        foreach ($inverterBatteryBundles as $bundle) {
            Bundles::create($bundle);
        }

        foreach ($solarInverterBatteryBundles as $bundle) {
            Bundles::create($bundle);
        }

        $this->command->info('Bundles seeded successfully!');
        $this->command->info('Created ' . count($inverterBatteryBundles) . ' Inverter + Battery bundles (LitePower1213, LitePower1225, LitePower1238)');
        $this->command->info('Created ' . count($solarInverterBatteryBundles) . ' Solar+Inverter+Battery bundles (SolarLitePower1213, SolarLitePower1225, SolarLitePower1238)');
    }
}
