<?php

namespace App\Enums;

/**
 * A status that can be shown with <x-status-badge>.
 * Badges always pair an icon with text so meaning never depends on colour alone.
 */
interface HasBadge
{
    public function label(): string;

    /** Tailwind classes for background + text colour. */
    public function tone(): string;

    /** A short decorative glyph shown before the label (hidden from screen readers). */
    public function icon(): string;
}
