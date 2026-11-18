<?php

namespace Tests\Feature\Clients;

use App\Filament\Resources\Clients\Pages\CreateClient;
use App\Filament\Resources\Clients\Pages\EditClient;
use App\Filament\Resources\Clients\Pages\ListClients;
use App\Models\Address;
use App\Models\Client;
use App\Models\Contact;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClientResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->officeStaff()->create());
    }

    public function test_can_create_a_client(): void
    {
        Livewire::test(CreateClient::class)
            ->fillForm([
                'type' => 'company',
                'name' => 'Acme SL',
                'tax_id' => 'b-1234 5678',
                'email' => 'info@acme.test',
                'phone' => '931234567',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('clients', ['name' => 'Acme SL', 'tax_id' => 'B12345678']);
    }

    public function test_name_is_required(): void
    {
        Livewire::test(CreateClient::class)
            ->fillForm(['name' => ''])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required']);

        $this->assertDatabaseCount('clients', 0);
    }

    public function test_email_must_be_valid(): void
    {
        Livewire::test(CreateClient::class)
            ->fillForm(['name' => 'X', 'email' => 'not-an-email'])
            ->call('create')
            ->assertHasFormErrors(['email' => 'email']);

        $this->assertDatabaseCount('clients', 0);
    }

    public function test_tax_id_must_be_unique_after_normalization(): void
    {
        Client::factory()->create(['tax_id' => 'B12345678']);

        Livewire::test(CreateClient::class)
            ->fillForm(['name' => 'Y', 'tax_id' => 'b-1234 5678'])
            ->call('create')
            ->assertHasFormErrors(['tax_id' => 'unique']);

        $this->assertDatabaseCount('clients', 1);
    }

    public function test_can_edit_a_client(): void
    {
        $client = Client::factory()->create();
        Livewire::test(EditClient::class, ['record' => $client->getRouteKey()])
            ->fillForm(['name' => 'New name'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('New name', $client->fresh()->name);
    }

    public function test_can_delete_a_client_with_its_contacts_and_addresses(): void
    {
        $client = Client::factory()->create();
        Contact::factory()->for($client)->create();
        Address::factory()->for($client)->create();

        Livewire::test(EditClient::class, ['record' => $client->getRouteKey()])
            ->callAction(TestAction::make('delete'));

        $this->assertModelMissing($client);
        $this->assertDatabaseCount('contacts', 0);
        $this->assertDatabaseCount('addresses', 0);
    }

    public function test_can_search_clients_by_name(): void
    {
        $a = Client::factory()->create(['name' => 'Alpha Repairs']);
        $b = Client::factory()->create(['name' => 'Beta Cleaning']);

        Livewire::test(ListClients::class)
            ->searchTable('Alpha')
            ->assertCanSeeTableRecords([$a])
            ->assertCanNotSeeTableRecords([$b]);
    }

    public function test_can_filter_by_active_status(): void
    {
        $active = Client::factory()->create();
        $inactive = Client::factory()->inactive()->create();

        Livewire::test(ListClients::class)
            ->filterTable('is_active', false)
            ->assertCanSeeTableRecords([$inactive])
            ->assertCanNotSeeTableRecords([$active]);
    }

    public function test_can_filter_by_type(): void
    {
        $company = Client::factory()->create();
        $person = Client::factory()->individual()->create();

        Livewire::test(ListClients::class)
            ->filterTable('type', 'individual')
            ->assertCanSeeTableRecords([$person])
            ->assertCanNotSeeTableRecords([$company]);
    }
}
