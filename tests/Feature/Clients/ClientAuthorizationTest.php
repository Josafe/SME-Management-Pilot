<?php

namespace Tests\Feature\Clients;

use App\Filament\Resources\Clients\ClientResource;
use App\Filament\Resources\Clients\Pages\EditClient;
use App\Filament\Resources\Clients\Pages\ListClients;
use App\Filament\Resources\Clients\Pages\ViewClient;
use App\Filament\Resources\Clients\RelationManagers\AddressesRelationManager;
use App\Filament\Resources\Clients\RelationManagers\ContactsRelationManager;
use App\Models\Address;
use App\Models\Client;
use App\Models\Contact;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ClientAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function managerRoles(): array
    {
        return [
            'admin' => ['admin'],
            'office staff' => ['officeStaff'],
        ];
    }

    private function userWithRole(string $state): User
    {
        return User::factory()->{$state}()->create();
    }

    public function test_user_without_role_cannot_access_the_panel_or_clients(): void
    {
        $client = Client::factory()->create();
        $this->actingAs(User::factory()->withoutRole()->create());

        $this->get('/admin')->assertForbidden();
        $this->get(ClientResource::getUrl('index'))->assertForbidden();
        $this->get(ClientResource::getUrl('view', ['record' => $client]))->assertForbidden();
    }

    public function test_guest_is_redirected_from_client_pages(): void
    {
        $this->get(ClientResource::getUrl('index'))->assertRedirect('/admin/login');
    }

    #[DataProvider('managerRoles')]
    public function test_managers_can_open_every_client_page(string $role): void
    {
        $client = Client::factory()->create();
        $this->actingAs($this->userWithRole($role));

        $this->get(ClientResource::getUrl('index'))->assertOk();
        $this->get(ClientResource::getUrl('create'))->assertOk();
        $this->get(ClientResource::getUrl('view', ['record' => $client]))->assertOk();
        $this->get(ClientResource::getUrl('edit', ['record' => $client]))->assertOk();
    }

    #[DataProvider('managerRoles')]
    public function test_managers_have_full_client_permissions(string $role): void
    {
        $user = $this->userWithRole($role);
        $client = Client::factory()->create();
        $contact = Contact::factory()->for($client)->create();
        $address = Address::factory()->for($client)->create();

        foreach (['viewAny', 'create', 'deleteAny'] as $ability) {
            $this->assertTrue($user->can($ability, Client::class), "Client::$ability");
            $this->assertTrue($user->can($ability, Contact::class), "Contact::$ability");
            $this->assertTrue($user->can($ability, Address::class), "Address::$ability");
        }

        foreach (['view', 'update', 'delete'] as $ability) {
            $this->assertTrue($user->can($ability, $client), "client $ability");
            $this->assertTrue($user->can($ability, $contact), "contact $ability");
            $this->assertTrue($user->can($ability, $address), "address $ability");
        }
    }

    #[DataProvider('managerRoles')]
    public function test_managers_can_delete_a_client_from_the_table(string $role): void
    {
        $client = Client::factory()->create();
        $this->actingAs($this->userWithRole($role));

        Livewire::test(ListClients::class)
            ->assertActionVisible(TestAction::make('edit')->table($client))
            ->selectTableRecords([$client->getKey()])
            ->callAction(TestAction::make('delete')->table()->bulk());

        $this->assertModelMissing($client);
    }

    #[DataProvider('managerRoles')]
    public function test_managers_can_manage_contacts_and_addresses_on_the_edit_page(string $role): void
    {
        $client = Client::factory()->create();
        $contact = Contact::factory()->for($client)->create();
        $address = Address::factory()->for($client)->create();
        $this->actingAs($this->userWithRole($role));

        Livewire::test(ContactsRelationManager::class, ['ownerRecord' => $client, 'pageClass' => EditClient::class])
            ->assertActionVisible(TestAction::make('create')->table())
            ->assertActionVisible(TestAction::make('edit')->table($contact))
            ->assertActionVisible(TestAction::make('delete')->table($contact));

        Livewire::test(AddressesRelationManager::class, ['ownerRecord' => $client, 'pageClass' => EditClient::class])
            ->assertActionVisible(TestAction::make('create')->table())
            ->assertActionVisible(TestAction::make('edit')->table($address))
            ->assertActionVisible(TestAction::make('delete')->table($address));
    }

    public function test_technician_has_read_only_client_access(): void
    {
        $client = Client::factory()->create();
        $contact = Contact::factory()->for($client)->create();
        $address = Address::factory()->for($client)->create();
        $technician = $this->userWithRole('technician');
        $this->actingAs($technician);

        $this->get(ClientResource::getUrl('index'))->assertOk();
        $this->get(ClientResource::getUrl('view', ['record' => $client]))->assertOk();
        $this->get(ClientResource::getUrl('create'))->assertForbidden();
        $this->get(ClientResource::getUrl('edit', ['record' => $client]))->assertForbidden();

        foreach (['create', 'deleteAny'] as $ability) {
            $this->assertFalse($technician->can($ability, Client::class), "Client::$ability");
            $this->assertFalse($technician->can($ability, Contact::class), "Contact::$ability");
            $this->assertFalse($technician->can($ability, Address::class), "Address::$ability");
        }

        foreach (['update', 'delete'] as $ability) {
            $this->assertFalse($technician->can($ability, $client), "client $ability");
            $this->assertFalse($technician->can($ability, $contact), "contact $ability");
            $this->assertFalse($technician->can($ability, $address), "address $ability");
        }
    }

    public function test_technician_sees_no_mutating_actions(): void
    {
        $client = Client::factory()->create();
        $contact = Contact::factory()->for($client)->create();
        $address = Address::factory()->for($client)->create();
        $this->actingAs($this->userWithRole('technician'));

        Livewire::test(ListClients::class)
            ->assertCanSeeTableRecords([$client])
            ->assertActionVisible(TestAction::make('view')->table($client))
            ->assertActionHidden(TestAction::make('edit')->table($client))
            ->assertActionHidden(TestAction::make('create'));

        Livewire::test(ViewClient::class, ['record' => $client->getRouteKey()])
            ->assertActionHidden(TestAction::make('edit'));

        // Even if a relation manager is mounted on the edit page, its actions are not authorized.
        Livewire::test(ContactsRelationManager::class, ['ownerRecord' => $client, 'pageClass' => EditClient::class])
            ->assertCanSeeTableRecords([$contact])
            ->assertActionHidden(TestAction::make('create')->table())
            ->assertActionHidden(TestAction::make('edit')->table($contact))
            ->assertActionHidden(TestAction::make('delete')->table($contact));

        Livewire::test(AddressesRelationManager::class, ['ownerRecord' => $client, 'pageClass' => EditClient::class])
            ->assertCanSeeTableRecords([$address])
            ->assertActionHidden(TestAction::make('create')->table())
            ->assertActionHidden(TestAction::make('edit')->table($address))
            ->assertActionHidden(TestAction::make('delete')->table($address));
    }
}
