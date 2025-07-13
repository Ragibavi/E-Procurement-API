<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Str;

class VendorTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_has_user_relationship()
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $vendor->user);
        $this->assertEquals($user->id, $vendor->user->id);
    }

    public function test_vendor_uses_uuid_on_create()
    {
        $user = User::factory()->create();
        $vendor = Vendor::create([
            'user_id' => $user->id,
            'company_name' => 'Test',
            'contact_person' => 'Contact',
            'phone' => '08123456789',
            'email' => 'test@example.com',
            'address' => 'Jl. Jalan',
        ]);

        $this->assertNotNull($vendor->id);
        $this->assertTrue(Str::isUuid($vendor->id));
    }

    public function test_vendor_fillable_attributes()
    {
        $data = [
            'id' => (string) Str::uuid(),
            'user_id' => 'some-user-id',
            'company_name' => 'Company',
            'contact_person' => 'Person',
            'phone' => '12345678',
            'email' => 'email@example.com',
            'address' => 'Address',
        ];

        $vendor = new Vendor($data);

        $this->assertEquals('Company', $vendor->company_name);
        $this->assertEquals('Person', $vendor->contact_person);
        $this->assertEquals('12345678', $vendor->phone);
    }
}
