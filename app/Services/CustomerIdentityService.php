<?php

namespace App\Services;

use App\Models\Party;
use App\Support\CustomerIdentity;
use Illuminate\Database\QueryException;

class CustomerIdentityService
{
    public function findCustomer(string $name, ?string $address, ?int $exceptId = null): ?Party
    {
        $keys = CustomerIdentity::keys($name, $address);

        return Party::query()
            ->where('party_type', 'customer')
            ->where('name_key', $keys['name_key'])
            ->where('address_key', $keys['address_key'])
            ->when($exceptId !== null, fn ($query) => $query->where('id', '!=', $exceptId))
            ->first();
    }

    public function customerExists(string $name, ?string $address, ?int $exceptId = null): bool
    {
        return $this->findCustomer($name, $address, $exceptId) !== null;
    }

    /**
     * @param  array{name?: string|null, address?: string|null, address_line?: string|null}  $attributes
     */
    public function syncIdentityKeys(Party $party, array $attributes = []): void
    {
        $name = array_key_exists('name', $attributes)
            ? (string) ($attributes['name'] ?? '')
            : (string) ($party->name ?? '');
        $address = array_key_exists('address', $attributes)
            ? ($attributes['address'] ?? '')
            : ($attributes['address_line'] ?? $party->address ?? '');

        $keys = CustomerIdentity::keys($name, is_string($address) ? $address : null);
        $party->name_key = $keys['name_key'];
        $party->address_key = $keys['address_key'];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createCustomer(array $attributes): Party
    {
        $party = new Party($attributes);
        $party->party_type = 'customer';
        $this->syncIdentityKeys($party, $attributes);

        if ($this->findCustomer((string) $party->name, $party->address) !== null) {
            throw new \InvalidArgumentException('A customer with this name and address already exists.');
        }

        try {
            $party->save();
        } catch (QueryException $exception) {
            if ($this->isDuplicateIdentityException($exception)) {
                throw new \InvalidArgumentException('A customer with this name and address already exists.', 0, $exception);
            }

            throw $exception;
        }

        return $party->fresh();
    }

    private function isDuplicateIdentityException(QueryException $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'uniq_parties_customer_identity')
            || str_contains($message, 'duplicate entry');
    }
}
