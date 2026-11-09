<?php

namespace Database\Seeders;

use App\Models\BusinessCategory;
use App\Models\BusinessType;
use Illuminate\Database\Seeder;

/**
 * Starter list of types a resident would recognize, each under one Business Category (D39). The PSIC section of each
 * still has to be checked against the PSA classification by the data member
 * (database.md, "business_types").
 */
class BusinessTypeSeeder extends Seeder
{
    /**
     * @var array<string, string>
     */
    private const array SECTIONS = [
        'A' => 'Agriculture, forestry and fishing',
        'C' => 'Manufacturing',
        'G' => 'Wholesale and retail trade; repair of motor vehicles and motorcycles',
        'I' => 'Accommodation and food service activities',
        'K' => 'Financial and insurance activities',
        'M' => 'Professional, scientific and technical activities',
        'P' => 'Education',
        'Q' => 'Human health and social work activities',
        'R' => 'Arts, entertainment and recreation',
        'S' => 'Other service activities',
    ];

    /**
     * @var list<array{0: string, 1: string, 2: string, 3: string}>
     */
    private const array TYPES = [
        ['sari-sari-store', 'Sari-Sari Store', 'G', 'retail'],
        ['grocery', 'Grocery', 'G', 'retail'],
        ['pharmacy', 'Pharmacy', 'G', 'retail'],
        ['hardware-store', 'Hardware Store', 'G', 'retail'],
        ['clothing-store', 'Clothing Store', 'G', 'retail'],
        ['school-supplies', 'School Supplies and Books', 'G', 'retail'],
        ['auto-repair', 'Auto and Motorcycle Repair', 'G', 'vehicle-motor-parts'],
        ['bakery', 'Bakery', 'C', 'food-processing'],
        ['water-refilling', 'Water Refilling Station', 'C', 'food-processing'],
        ['printing-shop', 'Printing Shop', 'C', 'other'],
        ['coffee-shop', 'Coffee Shop', 'I', 'food-and-beverage'],
        ['restaurant', 'Restaurant', 'I', 'food-and-beverage'],
        ['eatery', 'Carinderia or Eatery', 'I', 'food-and-beverage'],
        ['boarding-house', 'Boarding House', 'I', 'lodging-and-rentals'],
        ['hotel', 'Hotel or Inn', 'I', 'tourism'],
        ['bank', 'Bank', 'K', 'finance'],
        ['lending', 'Lending or Pawnshop', 'K', 'finance'],
        ['veterinary-clinic', 'Veterinary Clinic', 'M', 'health-services'],
        ['tutorial-center', 'Tutorial Center', 'P', 'education'],
        ['clinic', 'Medical Clinic', 'Q', 'health-services'],
        ['dental-clinic', 'Dental Clinic', 'Q', 'health-services'],
        ['gym', 'Gym or Fitness Center', 'R', 'personal-and-repair-services'],
        ['salon', 'Salon or Barbershop', 'S', 'personal-and-repair-services'],
        ['laundry', 'Laundry Shop', 'S', 'personal-and-repair-services'],
        ['repair-shop', 'Gadget and Appliance Repair', 'S', 'personal-and-repair-services'],
        ['crop-farm', 'Rice and Corn Farm', 'A', 'agriculture'],
        ['livestock-farm', 'Poultry and Livestock Farm', 'A', 'agriculture'],
        ['fishpond', 'Fishpond or Aquaculture', 'A', 'fishery'],
        ['agri-supply', 'Agricultural Supply Store', 'G', 'retail'],
        ['rice-mill', 'Rice Mill', 'C', 'food-processing'],
        ['online-seller', 'Online Seller', 'G', 'retail'],
        ['digital-services', 'Graphic and Digital Services', 'M', 'other'],
    ];

    public function run(): void
    {
        // Every type needs its category, so the starter categories exist first.
        $this->call(BusinessCategorySeeder::class);
        $categories = BusinessCategory::query()->pluck('id', 'code');

        foreach (self::TYPES as $order => [$code, $name, $section, $category]) {
            BusinessType::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'psic_section' => $section,
                    'psic_section_name' => self::SECTIONS[$section],
                    'business_category_id' => $categories[$category],
                    'sort_order' => $order + 1,
                ],
            );
        }
    }
}
