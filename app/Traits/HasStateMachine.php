<?php

namespace App\Traits;

use Illuminate\Support\Collection;

/**
 * Generic order state-machine helpers.
 *
 * Implementing models MUST define the following public class constants:
 *
 *   STATUS_TRANSITIONS : array<string, string[]>
 *     Map of current_status => [allowed_next_statuses].
 *
 *   STATUS_LABELS : array<string, string>
 *     Human-readable labels for each status value.
 *
 * They MAY also define:
 *   BLOCK_CANCEL_STATUSES : string[]
 *     Statuses from which cancellation is only allowed when force=true.
 */
trait HasStateMachine
{
    /**
     * Return the valid next statuses for this model's current status,
     * formatted as [{ value, label }] for use in API responses and forms.
     *
     * @return Collection<int, array{value: string, label: string}>
     */
    public function getNextStatuses(): Collection
    {
        $transitions = static::STATUS_TRANSITIONS[$this->status] ?? [];
        $labels      = static::STATUS_LABELS ?? [];

        return collect($transitions)->map(fn ($s) => [
            'value' => $s,
            'label' => $labels[$s] ?? ucwords(str_replace('_', ' ', $s)),
        ])->values();
    }

    /**
     * Check whether transitioning to $newStatus from the current status is valid.
     */
    public function canTransitionTo(string $newStatus): bool
    {
        return in_array($newStatus, static::STATUS_TRANSITIONS[$this->status] ?? [], true);
    }

    /**
     * Check whether this record is in one of the cancellation-blocking statuses.
     * Requires BLOCK_CANCEL_STATUSES to be defined on the model.
     */
    public function blocksCancellation(): bool
    {
        if (!defined('static::BLOCK_CANCEL_STATUSES')) {
            return false;
        }

        return in_array($this->status, static::BLOCK_CANCEL_STATUSES, true);
    }

    /**
     * Return the human-readable label for the current status.
     */
    public function statusLabel(): string
    {
        return static::STATUS_LABELS[$this->status]
            ?? ucwords(str_replace('_', ' ', $this->status));
    }
}
