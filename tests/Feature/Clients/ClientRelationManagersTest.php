<?php

namespace Tests\Feature\Clients;

use App\Filament\Resources\Clients\Pages\EditClient;
use App\Filament\Resources\Clients\RelationManagers\AddressesRelationManager;
use App\Filament\Resources\Clients\RelationManagers\ContactsRelationManager;
use App\Models\Client;
use App\Models\Contact;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClientRelationManagersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->officeStaff()->create());
    }

    public function test_can_create_a_primary_contact(): void
    {
        $client = Client::factory()->create();
        Livewire::test(ContactsRelationManager::class, ['ownerRecord' => $client, 'pageClass' => EditClient::class])
            ->callAction(TestAction::make('create')->table(), ['name' => 'Joan Puig', 'email' => 'joan@example.test', 'is_primary' => true])
            ->assertHasNoActionErrors();
        $this->assertEquals('Joan Puig', $client->primaryContact->name);
    }

    public function test_new_primary_contact_replaces_the_previous_one(): void
    {
        $client = Client::factory()->create();
        $old = Contact::factory()->for($client)->primary()->create();
        Livewire::test(ContactsRelationManager::class, ['ownerRecord' => $client, 'pageClass' => EditClient::class])
            ->callAction(TestAction::make('create')->table(), ['name' => 'New', 'is_primary' => true])
            ->assertHasNoActionErrors();
        $this->assertFalse($old->fresh()->is_primary);
        $this->assertEquals(1, $client->contacts()->where('is_primary', true)->count());
    }

    public function test_contact_name_is_required(): void
    {
        $client = Client::factory()->create();
        Livewire::test(ContactsRelationManager::class, ['ownerRecord' => $client, 'pageClass' => EditClient::class])
            ->callAction(TestAction::make('create')->table(), ['name' => ''])
            ->assertHasActionErrors(['name' => 'required']);
        $this->assertEquals(0, $client->contacts()->count());
    }

    public function test_contact_email_must_be_valid(): void
    {
        $client = Client::factory()->create();
        Livewire::test(ContactsRelationManager::class, ['ownerRecord' => $client, 'pageClass' => EditClient::class])
            ->callAction(TestAction::make('create')->table(), ['name' => 'X', 'email' => 'bad'])
            ->assertHasActionErrors(['email' => 'email']);
    }

    public function test_can_create_a_default_address(): void
    {
        $client = Client::factory()->create();
        Livewire::test(AddressesRelationManager::class, ['ownerRecord' => $client, 'pageClass' => EditClient::class])
            ->callAction(TestAction::make('create')->table(), ['label' => 'Head office', 'street' => 'Carrer de Mallorca 1', 'postal_code' => '08013', 'city' => 'Barcelona', 'province' => 'Barcelona', 'country' => 'ES', 'is_default' => true])
            ->assertHasNoActionErrors();
        $this->assertEquals('Head office', $client->defaultAddress->label);
    }

    public function test_address_required_fields(): void
    {
        $client = Client::factory()->create();
        Livewire::test(AddressesRelationManager::class, ['ownerRecord' => $client, 'pageClass' => EditClient::class])
            ->callAction(TestAction::make('create')->table(), ['label' => '', 'street' => '', 'postal_code' => '', 'city' => ''])
            ->assertHasActionErrors(['label' => 'required', 'street' => 'required', 'postal_code' => 'required', 'city' => 'required']);
    }

    public function test_address_country_must_have_two_letters(): void
    {
        $client = Client::factory()->create();
        Livewire::test(AddressesRelationManager::class, ['ownerRecord' => $client, 'pageClass' => EditClient::class])
            ->callAction(TestAction::make('create')->table(), ['label' => 'Head office', 'street' => 'Carrer de Mallorca 1', 'postal_code' => '08013', 'city' => 'Barcelona', 'province' => 'Barcelona', 'country' => 'ESP', 'is_default' => true])
            ->assertHasActionErrors(['country']);
    }

    public function test_can_delete_a_contact(): void
    {
        $client = Client::factory()->create();
        $contact = Contact::factory()->for($client)->create();
        Livewire::test(ContactsRelationManager::class, ['ownerRecord' => $client, 'pageClass' => EditClient::class])
            ->callAction(TestAction::make('delete')->table($contact));
        $this->assertModelMissing($contact);
        $this->assertModelExists($client);
    }
}
