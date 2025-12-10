<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = [
            [
                "name" => "Acme Corporation",
                "client_name" => "Acme Corporation",
                "email" => "admin@acme.com",
                "phone" => "+1-555-0100",
                "billing_address" => "123 Business St, New York, NY 10001",
                "shipping_address" => "123 Business St, New York, NY 10001",
                "gst_number" => "22AAAAA0000A1Z5",
                "is_active" => true,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "name" => "TechVista Solutions",
                "client_name" => "TechVista Solutions Pvt Ltd",
                "email" => "contact@techvista.com",
                "phone" => "+1-555-0200",
                "billing_address" =>
                    "456 Innovation Ave, San Francisco, CA 94102",
                "shipping_address" =>
                    "456 Innovation Ave, San Francisco, CA 94102",
                "gst_number" => "29BBBBB5555B1Z6",
                "is_active" => true,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "name" => "Global Foods Inc",
                "client_name" => "Global Foods India Pvt Ltd",
                "email" => "info@globalfoods.com",
                "phone" => "+1-555-0300",
                "billing_address" => "789 Commerce Blvd, Chicago, IL 60601",
                "shipping_address" => "789 Commerce Blvd, Chicago, IL 60601",
                "gst_number" => "27CCCCC1111C1Z7",
                "is_active" => true,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "name" => "Inactive Test Company",
                "client_name" => "Inactive Test Company",
                "email" => "test@inactive.com",
                "phone" => "+1-555-0400",
                "billing_address" => "999 Test Lane, Boston, MA 02101",
                "shipping_address" => "999 Test Lane, Boston, MA 02101",
                "gst_number" => null,
                "is_active" => false,
                "created_at" => now(),
                "updated_at" => now(),
            ],
        ];

        DB::table("companies")->insert($companies);
    }
}
