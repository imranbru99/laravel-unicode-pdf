<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Contracts;

interface FontRepositoryInterface
{
    /**
     * Register a custom font family definition.
     *
     * @param  array<string, mixed>  $fontDefinition
     */
    public function register(array $fontDefinition): void;

    /**
     * Check if a font family is registered.
     */
    public function has(string $family): bool;

    /**
     * Get font definition by family name.
     *
     * @return array<string, mixed>|null
     */
    public function get(string $family): ?array;

    /**
     * Get all registered font definitions.
     *
     * @return array<string, array<string, mixed>>
     */
    public function all(): array;

    /**
     * Unregister a font family.
     */
    public function remove(string $family): void;
}
