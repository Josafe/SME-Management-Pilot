<?php

namespace Tests\Feature\Clients;

use App\Models\Address;
use App\Models\Client;
use App\Models\Contact;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ClientDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_has_many_contacts_and_addresses(): void
    {
        $client = Client::factory()->create();
        Contact::factory(2)->for($client)->create();
        Address::factory(3)->for($client)->create();

        $this->assertEquals(2, $client->contacts()->count());
        $this->assertEquals(3, $client->addresses()->count());
    }

    public function test_deleting_a_client_deletes_its_contacts_and_addresses(): void
    {
        $client = Client::factory()->create();
        Contact::factory(1)->for($client)->create();
        Address::factory(1)->for($client)->create();

        $client->delete();

        $this->assertDatabaseCount('contacts', 0);
        $this->assertDatabaseCount('addresses', 0);
    }

    public function test_only_one_primary_contact_per_client(): void
    {
        $client = Client::factory()->create();
        $first = Contact::factory()->for($client)->primary()->create();
        $second = Contact::factory()->for($client)->primary()->create();

        $this->assertFalse($first->fresh()->is_primary);
        $this->assertTrue($second->fresh()->is_primary);
        $this->assertTrue($client->primaryContact->is($second));
    }

    public function test_only_one_default_address_per_client(): void
    {
        $client = Client::factory()->create();
        $first = Address::factory()->for($client)->asDefault()->create();
        $second = Address::factory()->for($client)->asDefault()->create();

        $this->assertFalse($first->fresh()->is_default);
        $this->assertTrue($second->fresh()->is_default);
        $this->assertTrue($client->defaultAddress->is($second));
    }

    public function test_primary_flags_are_independent_between_clients(): void
    {
        $client1 = Client::factory()->create();
        $client2 = Client::factory()->create();
        $contact1 = Contact::factory()->for($client1)->primary()->create();
        $contact2 = Contact::factory()->for($client2)->primary()->create();

        $this->assertTrue($contact1->fresh()->is_primary);
        $this->assertTrue($contact2->fresh()->is_primary);
    }

    public function test_tax_id_is_normalized(): void
    {
        $client1 = Client::factory()->create(['tax_id' => 'b-1234 5678.9']);
        $client2 = Client::factory()->create(['tax_id' => '']);

        $this->assertEquals('B123456789', $client1->tax_id);
        $this->assertNull($client2->tax_id);
    }

    public function test_database_rejects_a_second_primary_contact(): void
    {
        $client = Client::factory()->create();
        $primaryContact = Contact::factory()->for($client)->primary()->create();

        $this->expectException(QueryException::class);

        DB::table('contacts')->insert([
            'client_id' => $client->id,
            'name' => 'Other',
            'is_primary' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
